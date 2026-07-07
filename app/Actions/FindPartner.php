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
            Matchmaking::where('user_id', $userId)->update([
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $partnerId
            ]);
            Matchmaking::where('user_id', $partnerId)->update([
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $userId
            ]);

            Redis::setex("allow_signal:{$userId}:{$partnerId}", 60, 1);
            Redis::setex("allow_signal:{$partnerId}:{$userId}", 60, 1);
        });

        // ПРОВЕРКА СТАТУСА ДРУЖБЫ (v1.8)
        $isPartnerFriendOfUser = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $partnerId)
            ->exists();

        $isUserFriendOfPartner = DB::table('contacts')
            ->where('user_id', $partnerId)
            ->where('contact_id', $userId)
            ->exists();

        broadcast(new MatchFoundEvent($userId, $partnerId, $isPartnerFriendOfUser));
        broadcast(new MatchFoundEvent($partnerId, $userId, $isUserFriendOfPartner));

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