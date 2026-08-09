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
    // --- НОВОЕ: ОЧИСТКА ПРИЗРАКОВ В REDIS ---
    // Получаем все ключи очередей (high и low для всех стран)
    $queueKeys = Redis::keys('matchmaking_high_*');
    $queueKeys = array_merge($queueKeys, Redis::keys('matchmaking_low_*'));

    foreach ($queueKeys as $queue) {
        // Убираем префикс базы данных из ключа, если он есть (зависит от настроек Redis)
        $cleanKey = str_replace(config('database.redis.options.prefix'), '', $queue);
        
        $ids = Redis::lrange($cleanKey, 0, -1);
        foreach ($ids as $id) {
            // Если пользователя нет в базе как "ищущего" — удаляем из Redis
            $stillInDb = Matchmaking::where('user_id', $id)
                ->where('status', MatchmakingStatus::Searching)
                ->exists();

            if (!$stillInDb) {
                Redis::lrem($cleanKey, 0, $id);
            }
        }
    }

    // 1. Очистка пустых комнат (Spaces)
    Room::where('current_occupancy', '>', 0)
            ->where('updated_at', '<', now()->subSeconds(45))
            ->each(function($room) {
                $room->update(['current_occupancy' => 0]);
                broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
            });

    // 2. Очистка зависших матчей в рулетке
    $staleMatches = Matchmaking::where('updated_at', '<', now()->subSeconds(45))->get();

    foreach ($staleMatches as $match) {
        if ($match->partner_id) {
            $myId = $match->user_id;
            $partnerId = $match->partner_id;

            broadcast(new WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'from' => $myId
            ]));

            Matchmaking::where('user_id', $partnerId)->update([
                'status' => MatchmakingStatus::Searching,
                'partner_id' => null,
                'updated_at' => now()
            ]);

            Redis::del("allow_signal:{$myId}:{$partnerId}");
            Redis::del("allow_signal:{$partnerId}:{$myId}");
        }
        $match->delete();
    }
})->everyMinute();