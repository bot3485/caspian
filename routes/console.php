<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Room;
use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;
use Illuminate\Support\Facades\Redis;

Schedule::command('sync:last-seen')->everyFiveMinutes();
Schedule::command('app:reward-xp')->everyMinute();

Schedule::call(function () {
    // 1. Очистка пустых комнат (Spaces)
    // Если в комнате есть "онлайн", но никто не слал Heartbeat больше 60 секунд
    Room::where('current_occupancy', '>', 0)
            ->where('updated_at', '<', now()->subSeconds(45))
            ->each(function($room) {
                $room->update(['current_occupancy' => 0]);
                // Оповещаем тех, кто в лобби, что комната освободилась
                broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
            });

    // 2. Очистка зависших матчей в рулетке (и персональных звонков)
    $staleMatches = Matchmaking::where('updated_at', '<', now()->subSeconds(30))->get();

    foreach ($staleMatches as $match) {
        if ($match->partner_id) {
            $myId = $match->user_id;
            $partnerId = $match->partner_id;

            // Уведомляем партнера, что мы "отвалились"
            broadcast(new WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'from' => $myId
            ]));

            // Возвращаем живого партнера в поиск
            Matchmaking::where('user_id', $partnerId)->update([
                'status' => MatchmakingStatus::Searching,
                'partner_id' => null,
                'updated_at' => now()
            ]);

            // ИСПРАВЛЕНИЕ REDIS: Удаляем конкретные ключи доступа, а не маску
            Redis::del("allow_signal:{$myId}:{$partnerId}");
            Redis::del("allow_signal:{$partnerId}:{$myId}");
        }
        
        // Удаляем запись "мертвого" пользователя
        $match->delete();
    }
})->everyMinute();