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
        $this->removeFromQueue($userId);
        
        $user = User::find($userId);
        if (!$user || ($user->banned_until && $user->banned_until->isFuture())) return null;

        $myQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
        
        // Пытаемся достать кого-то из очереди
        $partnerId = null;
        $maxAttempts = 15;

        while ($maxAttempts-- > 0 && ($tempId = Redis::lpop($myQueue))) {
            $tempId = (int)$tempId;
            
            if ($tempId === $userId) continue;

            // Проверка блокировок
            $isBlocked = DB::table('blocks')
                ->where(fn($q) => $q->where('blocker_id', $userId)->where('blocked_id', $tempId))
                ->orWhere(fn($q) => $q->where('blocker_id', $tempId)->where('blocked_id', $userId))
                ->exists();

            if ($isBlocked) continue;

            // Если прошел проверки - это наш партнер
            $partnerId = $tempId;
            break;
        }

        if (!$partnerId) {
            // Никого не нашли - встаем в очередь сами
            Redis::rpush($myQueue, $userId);
            Matchmaking::updateOrCreate(
                ['user_id' => $userId],
                ['status' => MatchmakingStatus::Searching, 'updated_at' => now()]
            );
            return null;
        }

        // ПАРТНЕР НАЙДЕН - ФИНАЛИЗИРУЕМ
        return $this->finalizeMatch($userId, $partnerId, $user);
    }

    private function finalizeMatch(int $myId, int $partnerId, User $me): int
    {
        $partner = User::find($partnerId);
        if (!$partner) return 0;

        DB::transaction(function () use ($myId, $partnerId) {
            // Очищаем старые записи
            Matchmaking::whereIn('user_id', [$myId, $partnerId])->delete();

            // Создаем новые статусы
            Matchmaking::create(['user_id' => $myId, 'status' => MatchmakingStatus::Matched, 'partner_id' => $partnerId, 'updated_at' => now()]);
            Matchmaking::create(['user_id' => $partnerId, 'status' => MatchmakingStatus::Matched, 'partner_id' => $myId, 'updated_at' => now()]);

            // История встреч
            DB::table('interactions')->updateOrInsert(['user_id' => $myId, 'partner_id' => $partnerId], ['last_at' => now()]);
            DB::table('interactions')->updateOrInsert(['user_id' => $partnerId, 'partner_id' => $myId], ['last_at' => now()]);

            // Права на сигналы (WebRTC)
            Redis::setex("allow_signal:{$myId}:{$partnerId}", 3600, "1");
            Redis::setex("allow_signal:{$partnerId}:{$myId}", 3600, "1");
        });

        // ВАЖНО: Рассылаем события. Обратите внимание на префикс точки в JS!
        broadcast(new MatchFoundEvent($myId, [
            'id' => $partner->id, 'name' => $partner->name, 'level' => $partner->level,
            'rank_name' => $partner->rank_name, 'karma' => $partner->karma
        ], DB::table('contacts')->where('user_id', $myId)->where('contact_id', $partnerId)->exists()));
        
        broadcast(new MatchFoundEvent($partnerId, [
            'id' => $me->id, 'name' => $me->name, 'level' => $me->level,
            'rank_name' => $me->rank_name, 'karma' => $me->karma
        ], DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $myId)->exists()));

        return $partnerId;
    }

    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}