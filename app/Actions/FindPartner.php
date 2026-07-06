<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use App\Models\Matchmaking;
use App\Enums\MatchmakingStatus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class FindPartner
{
    private string $queueHigh = 'matchmaking_high';
    private string $queueLow  = 'matchmaking_low';

    public function execute(int $userId): ?int
    {
        $reputation = (int) Redis::get("user_reputation:{$userId}") ?: 0;
        $myQueue = ($reputation >= 3) ? $this->queueLow : $this->queueHigh;

        $partnerId = null;

        while ($tempPartnerId = Redis::lpop($myQueue)) {
            $tempPartnerId = (int)$tempPartnerId;
            if ($tempPartnerId === $userId) continue;

            $isSearching = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->exists();

            if ($isSearching) {
                $partnerId = $tempPartnerId;
                break;
            }
        }

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            return null;
        }

        // Атомарное соединение
        DB::transaction(function () use ($userId, $partnerId) {
            // Обновляем первого
            Matchmaking::where('user_id', $userId)->update([
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $partnerId
            ]);
            // Обновляем второго
            Matchmaking::where('user_id', $partnerId)->update([
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $userId
            ]);

            // Ключи для signaling
            Redis::setex("allow_signal:{$userId}:{$partnerId}", 60, 1);
            Redis::setex("allow_signal:{$partnerId}:{$userId}", 60, 1);
        });

        broadcast(new MatchFoundEvent($partnerId, $userId));
        broadcast(new MatchFoundEvent($userId, $partnerId));

        return $partnerId;
    }

    private function addToQueue(int $userId, string $queue): void
    {
        $this->removeFromQueue($userId);
        Redis::rpush($queue, $userId);
        Redis::expire($queue, 3600);
    }

    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}