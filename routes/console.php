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
    // 1. Очистка пустых комнат (Spaces) - сокращаем до 30 секунд
    Room::where('current_occupancy', '>', 0)
        ->where('updated_at', '<', now()->subSeconds(30))
        ->each(function($room) {
            $room->update(['current_occupancy' => 0]);
            broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
        });

    // 2. Очистка зависших матчей в рулетке
    // Если updated_at старше 30 секунд — считаем пользователя вылетевшим
    $staleMatches = Matchmaking::where('updated_at', '<', now()->subSeconds(30))->get();

    foreach ($staleMatches as $match) {
        if ($match->partner_id) {
            // Мгновенно освобождаем партнера, если он еще жив
            broadcast(new WebRTCSignalEvent($match->partner_id, [
                'type' => 'peer-disconnected',
                'from' => $match->user_id
            ]));

            Matchmaking::where('user_id', $match->partner_id)->update([
                'status' => MatchmakingStatus::Searching,
                'partner_id' => null,
                'updated_at' => now()
            ]);
        }
        $match->delete();
        
        // Чистим временные ключи прав доступа в Redis
        Redis::del("allow_signal:{$match->user_id}:*");
    }
})->everyMinute(); // В Laravel 13 можно использовать ->everySecond() для супер-быстрой очистки, если сервер позволяет