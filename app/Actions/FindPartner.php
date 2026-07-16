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
    $maxAttempts = 30; // Увеличим кол-во попыток
    $skippedIds = []; 

    while ($maxAttempts-- > 0 && ($tempId = Redis::lpop($partnerQueue))) {
        $tempId = (int)$tempId;
        
        // 1. Пропускаем себя
        if ($tempId === $userId || in_array($tempId, $skippedIds)) continue;

        $partnerUser = User::find($tempId);
        if (!$partnerUser) continue;

        // 2. ПРОВЕРКА ФИЛЬТРОВ (Приводим к нижнему регистру для надежности)
        $myTarget = strtolower($user->target_gender ?: 'all');
        $myGender = strtolower($user->gender ?: 'male');
        $pTarget  = strtolower($partnerUser->target_gender ?: 'all');
        $pGender  = strtolower($partnerUser->gender ?: 'male');

        $genderMatch = ($myTarget === 'all' || $myTarget === $pGender) &&
                       ($pTarget === 'all' || $pTarget === $myGender);

        if (!$genderMatch) {
            $skippedIds[] = $tempId;
            continue;
        }

        // 3. ПРОВЕРКА ВОЗРАСТА
        $myAge = (int)$user->age;
        $pAge  = (int)$partnerUser->age;
        
        $ageMatch = ($pAge >= ($user->target_age_min ?: 18) && $pAge <= ($user->target_age_max ?: 99)) &&
                    ($myAge >= ($partnerUser->target_age_min ?: 18) && $myAge <= ($partnerUser->target_age_max ?: 99));

        if (!$ageMatch) {
            $skippedIds[] = $tempId;
            continue;
        }

        // 4. Проверка: Жив ли партнер в БД (Окно 30 секунд)
        $matchEntry = Matchmaking::where('user_id', $tempId)
            ->where('status', MatchmakingStatus::Searching)
            ->where('updated_at', '>=', now()->subSeconds(30)) 
            ->first();

        if (!$matchEntry) continue;

        // 5. Черный список
        $isBlocked = DB::table('blocks')
            ->where(fn($q) => $q->where('blocker_id', $userId)->where('blocked_id', $tempId))
            ->orWhere(fn($q) => $q->where('blocker_id', $tempId)->where('blocked_id', $userId))
            ->exists();

        if ($isBlocked) {
            $skippedIds[] = $tempId;
            continue;
        }

        $partnerId = $tempId;
        break;
    }

    // Возвращаем пропущенных
    foreach ($skippedIds as $sid) {
        $sUser = User::find($sid);
        if ($sUser) {
            $sQueue = "{$baseQueue}_" . ($sUser->target_country ?: 'global');
            Redis::rpush($sQueue, $sid);
        }
    }

    if (!$partnerId) {
        // Добавляем себя в очереди
        $myTargetQ = "{$baseQueue}_" . ($user->target_country ?: 'global');
        Redis::rpush($myTargetQ, $userId);
        
        // Обязательно обновляем время в БД, чтобы нас видели другие
        Matchmaking::updateOrCreate(['user_id' => $userId], ['status' => MatchmakingStatus::Searching, 'updated_at' => now()]);
        return null;
    }

    return $this->finalizeMatch($userId, $partnerId, $user);
}

private function finalizeMatch(int $myId, int $partnerId, User $me): int
    {
        $partner = User::find($partnerId);
        if (!$partner) return 0;

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

        // Отправка события МНЕ (я получаю данные ПАРТНЕРА)
        broadcast(new MatchFoundEvent($myId, [
            'id' => $partner->id, 
            'name' => $partner->name, 
            'level' => $partner->level,
            'gender' => $partner->gender, // Данные собеседника
            'age' => $partner->age,       // Данные собеседника
            'badge' => $partner->prestige_badge,
            'rank_name' => $partner->rank_name, 
            'karma' => $partner->karma,
            'country_code' => $partner->country_code,
            'country_flag' => UserCountry::getFlag($partner->country_code),
            'common_interests' => $commonInterests,
            'blocked_count' => DB::table('blocks')->where('blocked_id', $partnerId)->count(),
            'ban_count' => $partner->ban_count,
        ], DB::table('contacts')->where('user_id', $myId)->where('contact_id', $partnerId)->exists()));
        
        // Отправка события ПАРТНЕРУ (партнер получает МОИ данные)
        broadcast(new MatchFoundEvent($partnerId, [
            'id' => $me->id, 
            'name' => $me->name, 
            'level' => $me->level,
            'gender' => $me->gender, // ТВОИ данные (исправлено с $partner на $me)
            'age' => $me->age,       // ТВОИ данные (исправлено с $partner на $me)
            'badge' => $me->prestige_badge,
            'rank_name' => $me->rank_name, 
            'karma' => $me->karma,
            'country_code' => $me->country_code,
            'country_flag' => UserCountry::getFlag($me->country_code),
            'common_interests' => $commonInterests,
            'blocked_count' => DB::table('blocks')->where('blocked_id', $me->id)->count(), 
            'ban_count' => $me->ban_count,
        ], DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $myId)->exists()));

        return $partnerId;
    }

    public function removeFromQueue(int $userId): void
    {
        $user = User::find($userId);
        $country = $user ? ($user->target_country ?: 'global') : 'global';

        foreach ([$this->queueHigh, $this->queueLow] as $base) {
            Redis::lrem("{$base}_{$country}", 0, $userId);
            Redis::lrem("{$base}_global", 0, $userId);
        }
    }
}