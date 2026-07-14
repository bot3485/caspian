<?php

namespace App\Actions;

use App\Models\Matchmaking;
use Illuminate\Support\Facades\Redis;

class LeaveChat
{
    public function execute(int $userId): void
    {
        // 1. Ищем любые матчи, где этот пользователь является либо автором, либо партнером
        $matches = Matchmaking::where('user_id', $userId)
            ->orWhere('partner_id', $userId)
            ->get();

        foreach ($matches as $match) {
            $currentUserId = $match->user_id;
            $partnerId = $match->partner_id;

            if ($partnerId) {
                // Удаляем ключи доступа в Redis для обоих участников
                Redis::del("allow_signal:{$currentUserId}:{$partnerId}");
                Redis::del("allow_signal:{$partnerId}:{$currentUserId}");

                // Определяем, кому отправить сигнал о разрыве
                $targetId = ($userId === $currentUserId) ? $partnerId : $currentUserId;

                // Уведомляем вторую сторону, что связь разорвана
                broadcast(new \App\Events\WebRTCSignalEvent($targetId, [
                    'type' => 'peer-disconnected',
                    'from' => $userId
                ]));

                // Очищаем запись у партнера, чтобы у него тоже сбросился статус
                Matchmaking::where('user_id', $targetId)->delete();
            }
        }

        // 2. Удаляем пользователя из очередей Redis рулетки[cite: 2]
        Redis::lrem('matchmaking_high', 0, $userId); //[cite: 2]
        Redis::lrem('matchmaking_low', 0, $userId); //[cite: 2]

        // 3. Полностью удаляем все записи, связанные с текущим пользователем из таблицы matchmakings
        Matchmaking::where('user_id', $userId)->delete();
        Matchmaking::where('partner_id', $userId)->update(['partner_id' => null, 'status' => 'searching']); 
    }
}