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
    // 1. Очистка пустых или зависших комнат (Spaces)
    Room::where('current_occupancy', '>', 0)
        ->where('updated_at', '<', now()->subSeconds(45))
        ->each(function($room) {
            $room->update(['current_occupancy' => 0]);
            broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
        });

    // 2. Очистка зависших матчей в рулетке (Heartbeat Cleanup)
    $staleMatches = Matchmaking::where('updated_at', '<', now()->subSeconds(90))->get();

    foreach ($staleMatches as $match) {
        if ($match->partner_id) {
            // Уведомляем партнера, что этот человек пропал
            broadcast(new WebRTCSignalEvent($match->partner_id, [
                'type' => 'peer-disconnected',
                'from' => $match->user_id
            ]));

            // Возвращаем живого партнера в режим поиска
            Matchmaking::where('user_id', $match->partner_id)->update([
                'status' => MatchmakingStatus::Searching,
                'partner_id' => null,
                'updated_at' => now()
            ]);
            
            // Возвращаем ID партнера в Redis очередь
            $p = User::find($match->partner_id);
            if ($p) {
                $queue = ($p->karma < 50) ? 'matchmaking_low' : 'matchmaking_high';
                Redis::rpush($queue, $p->id);
            }
        }
        $match->delete();
    }
})->everyMinute();