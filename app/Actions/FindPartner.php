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
     * Основная логика поиска партнера.
     */
public function execute(int $userId): ?int
    {
        // 1. Сначала ПРИНУДИТЕЛЬНО удаляем самого себя из всех очередей, 
        // прежде чем начать новый поиск, чтобы не найти свою же старую сессию.
        $this->removeFromQueue($userId);

        $user = User::find($userId);
        if (!$user) return null;

        if ($user->karma < 50) {
            sleep(rand(3, 5)); // Немного уменьшил задержку для комфорта
        }

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

            // ПРОВЕРКА: Жива ли запись в БД и обновлялась ли она недавно?
            $matchEntry = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                // Если запись не обновлялась более 20 секунд — партнер "отвалился"
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
            // КРИТИЧНО: Обновляем таймштамп в БД, чтобы другие видели, что мы "свежие"
            Matchmaking::where('user_id', $userId)->update(['updated_at' => now()]);
            return null;
        }

        DB::transaction(function () use ($userId, $partnerId) {
            Matchmaking::where('user_id', $userId)->update(['status' => MatchmakingStatus::Matched, 'partner_id' => $partnerId, 'updated_at' => now()]);
            Matchmaking::where('user_id', $partnerId)->update(['status' => MatchmakingStatus::Matched, 'partner_id' => $userId, 'updated_at' => now()]);

            Redis::setex("allow_signal:{$userId}:{$partnerId}", 60, 1);
            Redis::setex("allow_signal:{$partnerId}:{$userId}", 60, 1);
            Redis::setex("user_last_partner:{$userId}", 300, $partnerId);
        });

        broadcast(new MatchFoundEvent($userId, $partnerId, DB::table('contacts')->where('user_id', $userId)->where('contact_id', $partnerId)->exists()));
        broadcast(new MatchFoundEvent($partnerId, $userId, DB::table('contacts')->where('user_id', $partnerId)->where('contact_id', $userId)->exists()));

        return $partnerId;
    }

    /**
     * Добавление пользователя в очередь Redis.
     */
    private function addToQueue(int $userId, string $queue): void
    {
        $this->removeFromQueue($userId);
        Redis::rpush($queue, $userId);
    }

    /**
     * Полное удаление пользователя из всех очередей.
     */
    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}