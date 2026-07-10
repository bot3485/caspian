<?php

namespace App\Actions;

use App\Models\Matchmaking;
use App\Events\WebRTCSignalEvent;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class LeaveChat
{
    public function execute(int $userId): void
    {
        // 1. Удаляем из очередей Redis
        (new FindPartner())->removeFromQueue($userId);

        // 2. Находим текущий активный матч
        $match = Matchmaking::where('user_id', $userId)->first();
        
        if ($match && $match->partner_id) {
            $partnerId = $match->partner_id;

            // Чистим Redis права на сигналы
            Redis::del("allow_signal:{$userId}:{$partnerId}");
            Redis::del("allow_signal:{$partnerId}:{$userId}");

            // Отправляем партнеру сигнал, что мы ушли
            broadcast(new WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'from' => $userId
            ]));
            
            // Удаляем запись у партнера (чтобы он вернулся в idle)
            Matchmaking::where('user_id', $partnerId)->delete();
        }

        // 3. Удаляем свою запись поиска/матча
        Matchmaking::where('user_id', $userId)->delete();
    }
}