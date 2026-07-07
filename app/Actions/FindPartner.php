<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class FindPartner
{
    private string $queueHigh = 'matchmaking_high';
    private string $queueLow  = 'matchmaking_low';

    public function execute(int $userId): ?int
    {
        $user = User::find($userId);
        if (!$user) return null;

        $myInterests = is_array($user->interests) ? $user->interests : [];
        $reputation = (int) Redis::get("user_reputation:{$userId}") ?: 0;
        $myQueue = ($reputation >= 3) ? $this->queueLow : $this->queueHigh;

        $lastPartnerId = (int) Redis::get("user_last_partner:{$userId}");

        $partnerId = null;
        $skipped = [];

        while ($tempPartnerId = Redis::lpop($myQueue)) {
            $tempPartnerId = (int)$tempPartnerId;
            
            if ($tempPartnerId === $userId) continue;
            if ($tempPartnerId === $lastPartnerId) {
                $skipped[] = $tempPartnerId;
                continue;
            }

            // Проверяем, существует ли партнер в БД
            $partner = User::find($tempPartnerId);
            if (!$partner) continue;

            $isSearching = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->exists();

            if ($isSearching) {
                // Умный подбор: если у нас есть интересы, ищем совпадение
                $partnerInterests = is_array($partner->interests) ? $partner->interests : [];
                $common = array_intersect($myInterests, $partnerInterests);

                if (!empty($myInterests) && empty($common) && count($skipped) < 5) {
                    $skipped[] = $tempPartnerId;
                    continue;
                }

                $partnerId = $tempPartnerId;
                break;
            }
        }

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
            Redis::setex("user_last_partner:{$userId}", 300, $partnerId);
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
    }

    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}