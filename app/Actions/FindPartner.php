<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use Illuminate\Support\Facades\Redis;

class FindPartner
{
    private string $queueHigh = 'matchmaking_high'; // Для нормальных
    private string $queueLow  = 'matchmaking_low';  // Для тех, у кого 3+ жалобы

    public function execute(int $userId): ?int
    {
        // 1. Определяем репутацию (счетчик жалоб в Redis)
        // Ключ 'user_reputation:{id}' мы инкрементим в ReportController
        $reputation = (int) Redis::get("user_reputation:{$userId}") ?: 0;
        
        // 2. Выбираем очередь
        $myQueue = ($reputation >= 3) ? $this->queueLow : $this->queueHigh;

        // 3. Пытаемся найти партнера в ЭТОЙ ЖЕ очереди
        $partnerId = Redis::lpop($myQueue);

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            return null;
        }

        if ((int)$partnerId === $userId) {
            return $this->execute($userId);
        }

        // 4. Соединяем
        broadcast(new MatchFoundEvent(targetUserId: (int)$partnerId, partnerId: $userId));
        broadcast(new MatchFoundEvent(targetUserId: $userId, partnerId: (int)$partnerId));

        return (int)$partnerId;
    }

    private function addToQueue(int $userId, string $queue): void
    {
        // Очищаем отовсюду перед добавлением
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
        
        Redis::rpush($queue, $userId);
        Redis::expire($queue, 3600); // Очередь живет час
    }

    public function removeFromQueue(int $userId): void
    {
        Redis::lrem($this->queueHigh, 0, $userId);
        Redis::lrem($this->queueLow, 0, $userId);
    }
}