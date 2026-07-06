<?php

namespace App\Actions;

use App\Events\MatchFoundEvent;
use App\Models\Matchmaking;
use App\Enums\MatchmakingStatus;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class FindPartner
{
    private string $queueHigh = 'matchmaking_high';
    private string $queueLow  = 'matchmaking_low';

    public function execute(int $userId): ?int
    {
        $reputation = (int) Redis::get("user_reputation:{$userId}") ?: 0;
        $myQueue = ($reputation >= 3) ? $this->queueLow : $this->queueHigh;

        $partnerId = Redis::lpop($myQueue);

        if (!$partnerId) {
            $this->addToQueue($userId, $myQueue);
            return null;
        }

        $partnerId = (int)$partnerId;

        // 1. Пишем в MySQL (без транзакции, чтобы не ждать коммита)
        \App\Models\Matchmaking::updateOrCreate(
            ['user_id' => $userId],
            ['status' => \App\Enums\MatchmakingStatus::Matched, 'partner_id' => $partnerId]
        );
        \App\Models\Matchmaking::updateOrCreate(
            ['user_id' => $partnerId],
            ['status' => \App\Enums\MatchmakingStatus::Matched, 'partner_id' => $userId]
        );

        // 2. Пишем в Redis «Мгновенный пропуск» на 1 минуту
        // Это позволит контроллеру мгновенно подтвердить пару
        Redis::setex("allow_signal:{$userId}:{$partnerId}", 60, 1);
        Redis::setex("allow_signal:{$partnerId}:{$userId}", 60, 1);

        // 3. Небольшая задержка перед анонсом в сокеты (чтобы база успела записать)
        usleep(100000); // 100ms

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