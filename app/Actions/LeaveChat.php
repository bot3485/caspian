<?php

namespace App\Actions;

use App\Models\Matchmaking;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class LeaveChat
{
    public function execute(int $userId): void
    {
        // 1. Находим текущий активный матч перед удалением
        $match = Matchmaking::where('user_id', $userId)->first();
        
        if ($match && $match->partner_id) {
            $partnerId = $match->partner_id;

            // Удаляем конкретные ключи доступа (без Redis::keys)
            Redis::del("allow_signal:{$userId}:{$partnerId}");
            Redis::del("allow_signal:{$partnerId}:{$userId}");

            // Уведомляем партнера
            broadcast(new \App\Events\WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'from' => $userId
            ]));
            
            // Очищаем запись у партнера, чтобы он не "завис"
            Matchmaking::where('user_id', $partnerId)->delete();
        }

        // 2. Удаляем из очередей
        Redis::lrem('matchmaking_high', 0, $userId);
        Redis::lrem('matchmaking_low', 0, $userId);

        // 3. Удаляем свою запись
        Matchmaking::where('user_id', $userId)->delete();
    }
}