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

    /**
     * Основная логика поиска партнера с учетом блокировок и записи истории.
     */
    public function execute(int $userId): ?int
    {
        $this->removeFromQueue($userId);

        $user = User::find($userId);
        if (!$user) return null;

        if ($user->banned_until && $user->banned_until->isFuture()) return null;

        $myInterests = is_array($user->interests) ? $user->interests : [];
        $myQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
        
        $partnerId = null;
        $skipped = [];

        while ($tempPartnerId = Redis::lpop($myQueue)) {
            $tempPartnerId = (int)$tempPartnerId;
            
            if ($tempPartnerId === $userId) continue;

            // ПРОВЕРКА ЧЕРНОГО СПИСКА (В обе стороны)
            $isBlocked = DB::table('blocks')
                ->where(function($q) use ($userId, $tempPartnerId) {
                    $q->where('blocker_id', $userId)->where('blocked_id', $tempPartnerId);
                })
                ->orWhere(function($q) use ($userId, $tempPartnerId) {
                    $q->where('blocker_id', $tempPartnerId)->where('blocked_id', $userId);
                })->exists();

            if ($isBlocked) {
                continue; 
            }

            // Проверка активности партнера в БД
            $matchEntry = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->where('updated_at', '>=', now()->subSeconds(20))
                ->first();

            if ($matchEntry) {
                $partner = User::find($tempPartnerId);
                if (!$partner || ($partner->banned_until && $partner->banned_until->isFuture())) continue;

                $partnerInterests = is_array($partner->interests) ? $partner->interests : [];
                $common = array_intersect($myInterests, $partnerInterests);

                if (!empty($myInterests) && !empty($partnerInterests) && empty($common) && count($skipped) < 5) {
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
            Matchmaking::where('user_id', $userId)->update(['updated_at' => now()]);
            return null;
        }

        $partner = User::find($partnerId);
        $commonWithPartner = array_values(array_intersect($myInterests, (array)$partner->interests));
        
        DB::transaction(function () use ($userId, $partnerId) {
            // 1. Обновляем статусы поиска
            Matchmaking::where('user_id', $userId)->update([
                'status' => MatchmakingStatus::Matched, 
                'partner_id' => $partnerId, 
                'updated_at' => now()
            ]);
            Matchmaking::where('user_id', $partnerId)->update([
                'status' => MatchmakingStatus::Matched, 
                'partner_id' => $userId, 
                'updated_at' => now()
            ]);

            // 2. ЗАПИСЫВАЕМ В ИСТОРИЮ ВСТРЕЧ (Interactions)
            DB::table('interactions')->updateOrInsert(
                ['user_id' => $userId, 'partner_id' => $partnerId],
                ['last_at' => now()]
            );
            DB::table('interactions')->updateOrInsert(
                ['user_id' => $partnerId, 'partner_id' => $userId],
                ['last_at' => now()]
            );

            // 3. Права на сигналы (1 час)
            Redis::setex("allow_signal:{$userId}:{$partnerId}", 3600, 1);
            Redis::setex("allow_signal:{$partnerId}:{$userId}", 3600, 1);
        });

        // Отправляем события
        broadcast(new MatchFoundEvent($userId, [
            'id' => $partner->id,
            'name' => $partner->name,
            'level' => $partner->level,
            'rank_name' => $partner->rank_name,
            'karma' => $partner->karma,
            'common_interests' => $commonWithPartner,
        ], DB::table('contacts')->where('user_id', $userId)->where('contact_id', $partnerId)->exists()));
        
        broadcast(new MatchFoundEvent($partnerId, [
            'id' => $user->id,
            'name' => $user->name,
            'level' => $user->level,
            'rank_name' => $user->rank_name,
            'karma' => $user->karma,
            'common_interests' => $commonWithPartner,
        ], DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $userId)->exists()));

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