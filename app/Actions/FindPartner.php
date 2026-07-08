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
     * Основная логика поиска партнера с расширенной информацией.
     */
    public function execute(int $userId): ?int
    {
        // 1. Принудительно удаляем себя из очередей перед началом нового поиска
        $this->removeFromQueue($userId);

        $user = User::find($userId);
        if (!$user) return null;

        if ($user->banned_until && $user->banned_until->isFuture()) return null;

        $myInterests = is_array($user->interests) ? $user->interests : [];
        $myQueue = ($user->karma < 50) ? $this->queueLow : $this->queueHigh;
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

            // Проверка активности партнера в БД (обновление в последние 20 секунд)
            $matchEntry = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->where('updated_at', '>=', now()->subSeconds(20))
                ->first();

            if ($matchEntry) {
                $partner = User::find($tempPartnerId);
                if (!$partner || ($partner->banned_until && $partner->banned_until->isFuture())) continue;

                $partnerInterests = is_array($partner->interests) ? $partner->interests : [];
                $common = array_intersect($myInterests, $partnerInterests);

                // Если есть свои интересы, но нет общих с партнером — пропускаем (но не более 5 раз)
                if (!empty($myInterests) && !empty($partnerInterests) && empty($common) && count($skipped) < 5) {
                    $skipped[] = $tempPartnerId;
                    continue;
                }

                $partnerId = $tempPartnerId;
                break;
            }
        }

        // Возвращаем пропущенных в очередь
        foreach ($skipped as $sid) {
            Redis::rpush($myQueue, $sid);
        }

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            Matchmaking::where('user_id', $userId)->update(['updated_at' => now()]);
            return null;
        }

        // Данные партнера для меня
        $partner = User::find($partnerId);
        $commonWithPartner = array_values(array_intersect($myInterests, (array)$partner->interests));
        
        $partnerDataForMe = [
            'id' => $partner->id,
            'name' => $partner->name,
            'level' => $partner->level,
            'rank_name' => $partner->rank_name, // Предполагается наличие атрибута в модели User
            'karma' => $partner->karma,
            'common_interests' => $commonWithPartner,
        ];

        // Мои данные для партнера
        $myDataForPartner = [
            'id' => $user->id,
            'name' => $user->name,
            'level' => $user->level,
            'rank_name' => $user->rank_name,
            'karma' => $user->karma,
            'common_interests' => $commonWithPartner,
        ];

        DB::transaction(function () use ($userId, $partnerId) {
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

            Redis::setex("allow_signal:{$userId}:{$partnerId}", 60, 1);
            Redis::setex("allow_signal:{$partnerId}:{$userId}", 60, 1);
            Redis::setex("user_last_partner:{$userId}", 300, $partnerId);
        });

        // Отправляем события с полным набором данных
        broadcast(new MatchFoundEvent(
            $userId, 
            $partnerDataForMe, 
            DB::table('contacts')->where('user_id', $userId)->where('contact_id', $partnerId)->exists()
        ));
        
        broadcast(new MatchFoundEvent(
            $partnerId, 
            $myDataForPartner, 
            DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $userId)->exists()
        ));

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