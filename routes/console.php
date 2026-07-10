<?php

use Illuminate\Support\Facades\Schedule;
use App\Models\Room;
use App\Models\Matchmaking;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;

Schedule::command('sync:last-seen')->everyFiveMinutes();
Schedule::command('app:reward-xp')->everyMinute();

Schedule::call(function () {
    // 1. Очистка пустых комнат
    $expiredRooms = Room::where('current_occupancy', '>', 0)
        ->where('updated_at', '<', now()->subSeconds(40))
        ->get();

    foreach ($expiredRooms as $room) {
        $room->update(['current_occupancy' => 0]);
        broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
    }

    // 2. Очистка зависших матчей в рулетке (Heartbeat Cleanup)
    // Если пользователь был в матче, но его updated_at старый — значит он закрыл вкладку/умер интернет
    $staleMatches = Matchmaking::where('updated_at', '<', now()->subSeconds(45))
        ->get();

    foreach ($staleMatches as $match) {
        if ($match->partner_id) {
            // Уведомляем живого партнера о дисконнекте
            broadcast(new WebRTCSignalEvent($match->partner_id, [
                'type' => 'peer-disconnected',
                'from' => $match->user_id
            ]));

            // Освобождаем партнера
            Matchmaking::where('user_id', $match->partner_id)->delete();
        }
        $match->delete();
    }
})->everyMinute();