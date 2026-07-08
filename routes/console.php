<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Room;


Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('sync:last-seen')->everyFiveMinutes();
Schedule::command('app:reward-xp')->everyMinute();
Schedule::call(function () {
    // Если updated_at старый, значит там никого нет (никто не шлет пульс)
    $expiredRooms = Room::where('current_occupancy', '>', 0)
        ->where('updated_at', '<', now()->subSeconds(40))
        ->get();

    foreach ($expiredRooms as $room) {
        $room->update(['current_occupancy' => 0]);
        broadcast(new \App\Events\RoomOccupancyUpdated($room->uuid, 0));
    }
})->everyMinute();