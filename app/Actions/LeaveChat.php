<?php

namespace App\Actions;

use App\Models\Matchmaking;
use App\Events\WebRTCSignalEvent;
use Illuminate\Support\Facades\Redis;

class LeaveChat
{
    public function execute(int $userId): void
    {
        // 1. Удаляем из очереди поиска Redis
        (new FindPartner())->removeFromQueue($userId);

        // 2. Находим текущий активный матч в БД
        $match = Matchmaking::where('user_id', $userId)->first();
        
        if ($match && $match->partner_id) {
            $partnerId = $match->partner_id;

            // Удаляем быстрые ключи Redis
            Redis::del("allow_signal:{$userId}:{$partnerId}");
            Redis::del("allow_signal:{$partnerId}:{$userId}");

            // Сигнализируем партнеру, что мы отключились
            broadcast(new WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'from' => $userId
            ]));
            
            // Удаляем запись у партнера
            Matchmaking::where('user_id', $partnerId)->delete();
        }

        // 3. Удаляем свою запись
        Matchmaking::where('user_id', $userId)->delete();
    }
}