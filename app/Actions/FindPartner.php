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

        // 1. Определение очереди (Карма + Страна поиска)
        $baseQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
        $targetFilter = $user->target_country ?: 'global';
        $partnerQueue = "{$baseQueue}_{$targetFilter}";
        
        $myInterests = $user->interests ?: [];
        $partnerId = null;
        $maxAttempts = 40; 
        $skippedIds = []; 
        $compatibleWithoutInterests = []; // "Запасные" (подходят по фильтрам, но нет общих интересов)

        while ($maxAttempts-- > 0 && ($tempId = Redis::lpop($partnerQueue))) {
            $tempId = (int)$tempId;
            
            if (Redis::exists("user_left:{$tempId}")) {
                continue; 
            }

            if ($tempId === $userId || in_array($tempId, $skippedIds)) continue;

            $lockKey = "match_lock:{$tempId}";
            if (!Redis::set($lockKey, "1", "EX", 2, "NX")) {
                continue; 
            }

            $partnerUser = User::find($tempId);

            // --- КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ ТУТ ---
            // Сначала проверяем, жива ли запись в БД в принципе (Heartbeat)
            $dbEntry = Matchmaking::where('user_id', $tempId)
                ->where('status', MatchmakingStatus::Searching)
                ->where('updated_at', '>=', now()->subSeconds(35)) // Чуть больше запас, чем в кроне
                ->exists();

            if (!$partnerUser || !$dbEntry) {
                // Если юзера нет или он "протух" в БД - удаляем замок и НЕ добавляем в skippedIds
                // Он просто вылетает из Redis (так как мы уже сделали lpop)
                Redis::del($lockKey);
                continue;
            }

            // Теперь проверяем фильтры (пол, возраст, ЧС)
            if (!$this->isCompatible($user, $partnerUser)) {
                // Если он живой, но не подходит нам по полу/возрасту - запоминаем, чтобы вернуть в очередь в конце
                $skippedIds[] = $tempId;
                Redis::del($lockKey); 
                continue;
            }

            // 3. ПРИОРИТЕТ ПО ИНТЕРЕСАМ
            $pInterests = $partnerUser->interests ?: [];
            $hasCommon = !empty(array_intersect($myInterests, $pInterests));

            if ($hasCommon) {
                $partnerId = $tempId;
                break; // Нашли идеальный матч!
            } else {
                // Подходит по всем фильтрам, но интересы разные. 
                // Сохраняем и держим замок (lock), чтобы его не забрал кто-то другой, пока мы ищем "идеального".
                $compatibleWithoutInterests[] = ['id' => $tempId, 'lock' => $lockKey];
                
                // Если нашли уже 3-5 подходящих без интересов, можно остановиться, чтобы не грузить Redis
                if (count($compatibleWithoutInterests) >= 5) break;
            }
        }

        // 4. ВЫБОР ЛУЧШЕГО ИЗ ДОСТУПНЫХ
        if (!$partnerId && !empty($compatibleWithoutInterests)) {
            $choice = array_shift($compatibleWithoutInterests);
            $partnerId = $choice['id'];
        }

        // Освобождаем неиспользованные замки
        foreach ($compatibleWithoutInterests as $backup) {
            Redis::del($backup['lock']);
        }

        // 5. ВОЗВРАТ ТЕХ, КТО НЕ ПОДОШЕЛ, В ОЧЕРЕДЬ
        foreach ($skippedIds as $sid) {
            $sUser = User::find($sid);
            if ($sUser) {
                $sQueue = "{$baseQueue}_" . ($sUser->target_country ?: 'global');
                Redis::rpush($sQueue, $sid);
            }
        }

        // 6. ЕСЛИ ПАРТНЕР НЕ НАЙДЕН - СТАНОВИМСЯ В ОЧЕРЕДЬ
        if (!$partnerId) {
            $myTargetQ = "{$baseQueue}_{$targetFilter}";
            Redis::rpush($myTargetQ, $userId);
            Matchmaking::updateOrCreate(
                ['user_id' => $userId], 
                ['status' => MatchmakingStatus::Searching, 'updated_at' => now()]
            );
            return null;
        }

        // Чистим замок выбранного партнера перед финализацией
        Redis::del("match_lock:{$partnerId}");
        
        return $this->finalizeMatch($userId, $partnerId, $user);
    }

    private function isCompatible(User $me, User $p): bool
    {
        // 1. Гендерный фильтр (взаимный)
        $myTarget = strtolower($me->target_gender ?: 'all');
        $pTarget  = strtolower($p->target_gender ?: 'all');
        
        $genderMatch = ($myTarget === 'all' || $myTarget === $p->gender) &&
                       ($pTarget === 'all' || $pTarget === $me->gender);
        if (!$genderMatch) return false;

        // 2. Возрастной фильтр (взаимный)
        $ageMatch = ($p->age >= ($me->target_age_min ?: 18) && $p->age <= ($me->target_age_max ?: 99)) &&
                    ($me->age >= ($p->target_age_min ?: 18) && $me->age <= ($p->target_age_max ?: 99));
        if (!$ageMatch) return false;

        // 3. Проверка активности в БД (Heartbeat)
        $isAlive = Matchmaking::where('user_id', $p->id)
            ->where('status', MatchmakingStatus::Searching)
            ->where('updated_at', '>=', now()->subSeconds(30)) 
            ->exists();
        if (!$isAlive) return false;

        // 4. Черный список
        $isBlocked = DB::table('blocks')
            ->where(fn($q) => $q->where('blocker_id', $me->id)->where('blocked_id', $p->id))
            ->orWhere(fn($q) => $q->where('blocker_id', $p->id)->where('blocked_id', $me->id))
            ->exists();
        if ($isBlocked) return false;

        return true;
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

        // ПАРТНЕР для МЕНЯ
        broadcast(new MatchFoundEvent($myId, [
            'id' => $partner->hashid, // МЕНЯЕМ id на hashid
            'name' => $partner->name, 
            'level' => $partner->level,
            'gender' => $partner->gender,
            'age' => $partner->age,
            'badge' => $partner->prestige_badge,
            'rank_name' => $partner->rank_name, 
            'karma' => $partner->karma,
            'country_code' => $partner->country_code,
            'country_flag' => UserCountry::getFlag($partner->country_code),
            'common_interests' => $commonInterests,
            'blocked_count' => DB::table('blocks')->where('blocked_id', $partnerId)->count(),
            'ban_count' => $partner->ban_count,
            'vpn' => (bool)$partner->is_vpn,
        ], DB::table('contacts')->where('user_id', $myId)->where('contact_id', $partnerId)->exists()));
        
        // Я для ПАРТНЕРА
        broadcast(new MatchFoundEvent($partnerId, [
            'id' => $me->hashid, // МЕНЯЕМ id на hashid
            'name' => $me->name, 
            'level' => $me->level,
            'gender' => $me->gender,
            'age' => $me->age,
            'badge' => $me->prestige_badge,
            'rank_name' => $me->rank_name, 
            'karma' => $me->karma,
            'country_code' => $me->country_code,
            'country_flag' => UserCountry::getFlag($me->country_code),
            'common_interests' => $commonInterests,
            'blocked_count' => DB::table('blocks')->where('blocked_id', $me->id)->count(), 
            'ban_count' => $me->ban_count,
            'vpn' => (bool)$me->is_vpn,
        ], DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $myId)->exists()));

        return $partnerId;
    }

    public function removeFromQueue(int $userId): void
    {
        $user = User::find($userId);
        $country = $user ? ($user->target_country ?: 'global') : 'global';

        // 1. Ставим флаг в Redis, что пользователь вышел (на 30 сек)
        // Это позволит методу execute() мгновенно пропустить его, если он еще в списке
        Redis::setex("user_left:{$userId}", 30, "1");

        // 2. Стандартное удаление (пусть остается как фоновая чистка)
        foreach ([$this->queueHigh, $this->queueLow] as $base) {
            Redis::lrem("{$base}_{$country}", 0, $userId);
            Redis::lrem("{$base}_global", 0, $userId);
        }
    }
}