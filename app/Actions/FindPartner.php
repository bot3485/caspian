<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use App\Models\Matchmaking;
use App\Enums\MatchmakingStatus;
use Illuminate\Support\Facades\Redis;

class FindPartner
{
    private string $queueHigh = 'matchmaking_high';
    private string $queueLow  = 'matchmaking_low';

    public function execute(int $userId): ?int
    {
        $reputation = (int) Redis::get("user_reputation:{$userId}") ?: 0;
        $myQueue = ($reputation >= 3) ? $this->queueLow : $this->queueHigh;

        $partnerId = null;

        // ЦИКЛ ПРОВЕРКИ: достаем из Redis пока не найдем реально ищущего партнера
        while ($tempPartnerId = Redis::lpop($myQueue)) {
            $tempPartnerId = (int)$tempPartnerId;

            if ($tempPartnerId === $userId) continue;

            // СТРОГАЯ ПРОВЕРКА: ищет ли этот партнер сейчас по данным MySQL?
            $isSearching = Matchmaking::where('user_id', $tempPartnerId)
                ->where('status', MatchmakingStatus::Searching)
                ->exists();

            if ($isSearching) {
                $partnerId = $tempPartnerId;
                break;
            }
            // Если партнер в Redis есть, но в MySQL статуса Searching нет - значит это "призрак", идем дальше
        }

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            return null;
        }

        // СОЕДИНЯЕМ
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

        usleep(100000); // 100ms задержка для БД

        broadcast(new MatchFoundEvent(targetUserId: $partnerId, partnerId: $userId));
        broadcast(new MatchFoundEvent(targetUserId: $userId, partnerId: $partnerId));

        return $partnerId;
    }

    private function addToQueue(int $userId, string $queue): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
        Redis::rpush($queue, $userId);
        Redis::expire($queue, 3600);
    }

    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}