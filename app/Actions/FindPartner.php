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

        // v1.9: Получаем ID последнего партнера
        $lastPartnerId = (int) Redis::get("user_last_partner:{$userId}");

        $partnerId = null;
        $skipped = [];

        while ($tempPartnerId = Redis::lpop($myQueue)) {
            $tempPartnerId = (int)$tempPartnerId;
            
            if ($tempPartnerId === $userId) continue;

            // v1.9: Пропускаем, если это тот же человек, что был только что
            if ($tempPartnerId === $lastPartnerId) {
                $skipped[] = $tempPartnerId;
                continue;
            }

            $isSearching = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->exists();

            if ($isSearching) {
                $partnerId = $tempPartnerId;
                break;
            }
        }

        // Возвращаем пропущенных обратно в очередь
        foreach ($skipped as $sid) {
            Redis::rpush($myQueue, $sid);
        }

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            return null;
        }

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
            
            // v1.9: Запоминаем последнего партнера на 5 минут
            Redis::setex("user_last_partner:{$userId}", 300, $partnerId);
            Redis::setex("user_last_partner:{$partnerId}", 300, $userId);
        });

        $isPartnerFriendOfUser = DB::table('contacts')->where('user_id', $userId)->where('contact_id', $partnerId)->exists();
        $isUserFriendOfPartner = DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $userId)->exists();

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