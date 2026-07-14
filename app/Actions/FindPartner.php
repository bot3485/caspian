<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;
use App\Enums\UserCountry;
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

        // Определяем приоритетность очереди по карме
        $baseQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
        
        // 1. Формируем название очереди, в которую мы заглянем в поисках партнера.
        // Если у нас стоит конкретный фильтр (target_country), мы ищем людей ТОЛЬКО в этой очереди.
        // Если стоит 'global', мы проверяем очередь 'global'.
        $targetFilter = $user->target_country ?: 'global';
        $partnerQueue = "{$baseQueue}_{$targetFilter}";
        
        $partnerId = null;
        $maxAttempts = 20;

        while ($maxAttempts-- > 0 && ($tempId = Redis::lpop($partnerQueue))) {
            $tempId = (int)$tempId;
            if ($tempId === $userId) continue;

            // Проверка валидности партнера в БД
            $matchEntry = Matchmaking::where('user_id', $tempId)
                ->where('status', MatchmakingStatus::Searching)
                ->where('updated_at', '>=', now()->subSeconds(10)) 
                ->first();

            if (!$matchEntry) {
                continue;
            }

            // Дополнительная перекрестная проверка фильтра стран:
            // Подходит ли НАША страна под фильтр партнера (если у него не global)
            $partnerUser = User::find($tempId);
            if ($partnerUser && $partnerUser->target_country !== 'global' && $partnerUser->target_country !== $user->country_code) {
                // Возвращаем его обратно в его очередь, так как мы ему не подходим по гео-фильтру
                Redis::rpush("{$baseQueue}_{$partnerUser->target_country}", $tempId);
                continue;
            }

            // Проверка черного списка
            $isBlocked = DB::table('blocks')
                ->where(fn($q) => $q->where('blocker_id', $userId)->where('blocked_id', $tempId))
                ->orWhere(fn($q) => $q->where('blocker_id', $tempId)->where('blocked_id', $userId))
                ->exists();

            if ($isBlocked) continue;

            $partnerId = $tempId;
            break;
        }

        if (!$partnerId) {
            // Если никого не нашли, добавляем СЕБЯ в очередь, соответствующую НАШЕЙ стране.
            // Теперь другие пользователи, ищущие нашу страну (или 'global'), смогут нас найти.
            $myQueueName = "{$baseQueue}_" . ($user->country_code ?: 'us');
            Redis::rpush($myQueueName, $userId);
            
            // Также дублируем себя в глобальную очередь, чтобы нас могли найти те, кто ищет 'global'
            Redis::rpush("{$baseQueue}_global", $userId);
            return null;
        }

        return $this->finalizeMatch($userId, $partnerId, $user);
    }

    private function finalizeMatch(int $myId, int $partnerId, User $me): int
    {
        $partner = User::find($partnerId);
        if (!$partner) return 0;

        // Вычисляем общие интересы
        $myInterests = $me->interests ?? [];
        $partnerInterests = $partner->interests ?? [];
        $commonInterests = array_values(array_intersect($myInterests, $partnerInterests));

        DB::transaction(function () use ($myId, $partnerId) {
            Matchmaking::whereIn('user_id', [$myId, $partnerId])->delete();

            Matchmaking::create(['user_id' => $myId, 'status' => MatchmakingStatus::Matched, 'partner_id' => $partnerId, 'updated_at' => now()]);
            Matchmaking::create(['user_id' => $partnerId, 'status' => MatchmakingStatus::Matched, 'partner_id' => $myId, 'updated_at' => now()]);

            DB::table('interactions')->updateOrInsert(['user_id' => $myId, 'partner_id' => $partnerId], ['last_at' => now()]);
            DB::table('interactions')->updateOrInsert(['user_id' => $partnerId, 'partner_id' => $myId], ['last_at' => now()]);

            Redis::setex("allow_signal:{$myId}:{$partnerId}", 3600, "1");
            Redis::setex("allow_signal:{$partnerId}:{$myId}", 3600, "1");
        });

        // Передаем Emoji-флаг и код страны в сокет-событие MatchFoundEvent
        broadcast(new MatchFoundEvent($myId, [
            'id' => $partner->id, 
            'name' => $partner->name, 
            'level' => $partner->level,
            'rank_name' => $partner->rank_name, 
            'karma' => $partner->karma,
            'country_code' => $partner->country_code,
            'country_flag' => UserCountry::getFlag($partner->country_code), // <--- Генерируем Emoji-флаг
            'common_interests' => $commonInterests 
        ], DB::table('contacts')->where('user_id', $myId)->where('contact_id', $partnerId)->exists()));
        
        broadcast(new MatchFoundEvent($partnerId, [
            'id' => $me->id, 
            'name' => $me->name, 
            'level' => $me->level,
            'rank_name' => $me->rank_name, 
            'karma' => $me->karma,
            'country_code' => $me->country_code,
            'country_flag' => UserCountry::getFlag($me->country_code), // <--- И сюда
            'common_interests' => $commonInterests 
        ], DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $myId)->exists()));

        return $partnerId;
    }

    public function removeFromQueue(int $userId): void
    {
        $user = User::find($userId);
        $country = $user ? $user->country_code : 'us';

        // Чистим все возможные очереди Redis для этого юзера
        foreach ([$this->queueHigh, $this->queueLow] as $base) {
            Redis::lrem("{$base}_global", 0, $userId);
            Redis::lrem("{$base}_{$country}", 0, $userId);
        }
    }
}