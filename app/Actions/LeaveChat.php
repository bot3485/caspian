<?php

namespace App\Actions;

use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;
use Illuminate\Support\Facades\Redis;

class LeaveChat
{
    public function execute(int $userId): void
    {
        // 1. Ищем текущий активный матч пользователя
        $match = Matchmaking::where('user_id', $userId)
            ->orWhere('partner_id', $userId)
            ->first();

        if ($match) {
            // Определяем, кто в этой паре партнер
            $partnerId = ($match->user_id === $userId) ? $match->partner_id : $match->user_id;

            if ($partnerId) {
                // Удаляем ключи доступа в Redis для обоих участников
                Redis::del("allow_signal:{$userId}:{$partnerId}");
                Redis::del("allow_signal:{$partnerId}:{$userId}");

                // Уведомляем партнера, что связь разорвана
                broadcast(new \App\Events\WebRTCSignalEvent($partnerId, [
                    'type' => 'peer-disconnected',
                    'from' => $userId
                ]));

                // ВАЖНО: Не удаляем партнера, а возвращаем его в состояние поиска
                Matchmaking::where('user_id', $partnerId)->update([
                    'status' => MatchmakingStatus::Searching,
                    'partner_id' => null,
                    'updated_at' => now()
                ]);
            }
        }

        // 2. Очистка очередей в Redis (с учетом стран)
        $user = User::find($userId);
        $country = $user ? ($user->target_country ?: 'global') : 'global';

        // Удаляем из всех возможных списков
        $queues = ['matchmaking_high', 'matchmaking_low'];
        foreach ($queues as $base) {
            Redis::lrem("{$base}_{$country}", 0, $userId);
            Redis::lrem("{$base}_global", 0, $userId);
        }

        // 3. Полностью удаляем запись самого уходящего пользователя из очереди
        Matchmaking::where('user_id', $userId)->delete();
        
        // На случай если пользователь где-то остался в роли партнера
        Matchmaking::where('partner_id', $userId)->update([
            'partner_id' => null, 
            'status' => MatchmakingStatus::Searching,
            'updated_at' => now()
        ]);
    }
}