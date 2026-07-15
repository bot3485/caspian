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

    $baseQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
    $targetFilter = $user->target_country ?: 'global';
    $partnerQueue = "{$baseQueue}_{$targetFilter}";
    
    $partnerId = null;
    $maxAttempts = 15; // Уменьшим лимит попыток для скорости
    $skippedIds = []; // Сюда сохраним тех, кто не подошел по фильтру

    while ($maxAttempts-- > 0 && ($tempId = Redis::lpop($partnerQueue))) {
        $tempId = (int)$tempId;
        
        // Пропускаем себя или тех, кого уже видели в этом цикле
        if ($tempId === $userId || in_array($tempId, $skippedIds)) continue;

        $partnerUser = User::find($tempId);
        if (!$partnerUser) continue;

        // Проверка: Жив ли еще партнер в очереди (обновлялся ли за 10 сек)
        $matchEntry = Matchmaking::where('user_id', $tempId)
            ->where('status', MatchmakingStatus::Searching)
            ->where('updated_at', '>=', now()->subSeconds(10)) 
            ->first();

        if (!$matchEntry) continue;

        // КРОСС-ЧЕК ФИЛЬТРА: Подходим ли мы партнеру?
        // Если партнер ищет конкретную страну, и это не МЫ.
        if ($partnerUser->target_country !== 'global' && $partnerUser->target_country !== $user->country_code) {
            $skippedIds[] = $tempId; // Запоминаем, чтобы не брать его снова в этом цикле
            continue;
        }

        // Проверка черного списка
        $isBlocked = DB::table('blocks')
            ->where(fn($q) => $q->where('blocker_id', $userId)->where('blocked_id', $tempId))
            ->orWhere(fn($q) => $q->where('blocker_id', $tempId)->where('blocked_id', $userId))
            ->exists();

        if ($isBlocked) {
            $skippedIds[] = $tempId;
            continue;
        }

        // Если дошли сюда — партнер идеален
        $partnerId = $tempId;
        break;
    }

    // ВОЗВРАЩАЕМ неподходящих обратно в их очереди, чтобы их нашел кто-то другой
    foreach ($skippedIds as $sid) {
        $sUser = User::find($sid);
        if ($sUser) {
            $sQueue = "{$baseQueue}_" . ($sUser->target_country ?: 'global');
            Redis::rpush($sQueue, $sid);
        }
    }

    if (!$partnerId) {
        // Добавляем себя в очереди: в свою страну и в глобал
        $myCountryQueue = "{$baseQueue}_" . ($user->country_code ?: 'us');
        Redis::rpush($myCountryQueue, $userId);
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
            'badge' => $partner->prestige_badge,
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
            'badge' => $me->prestige_badge,
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