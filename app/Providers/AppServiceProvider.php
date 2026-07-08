<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\Events\PresenceChannelMemberJoined;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;
use App\Models\Room;
use App\Events\RoomOccupancyUpdated;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 1. Слушаем событие входа в Presence Channel
        Event::listen(PresenceChannelMemberJoined::class, function ($event) {
            $this->updateRoomOccupancy($event->channel->name, 'joined');
        });

        // 2. Слушаем событие выхода из Presence Channel
        Event::listen(PresenceChannelMemberLeft::class, function ($event) {
            $this->updateRoomOccupancy($event->channel->name, 'left');
        });
    }

    /**
     * Универсальный метод обновления онлайна в комнате.
     */
    protected function updateRoomOccupancy(string $channelName, string $action): void
    {
        // Улучшенное регулярное выражение (добавлен префикс presence-?)
        if (preg_match('/(?:presence-)?room\.([a-f0-9\-]{36})/', $channelName, $matches)) {
            $uuid = $matches[1];
            $room = \App\Models\Room::where('uuid', $uuid)->first();
            
            if ($room) {
                if ($action === 'joined') {
                    $room->increment('current_occupancy');
                } else {
                    // Используем MAX(0, ...), чтобы не уйти в минус при багах
                    \DB::table('rooms')->where('id', $room->id)->update([
                        'current_occupancy' => \DB::raw('GREATEST(current_occupancy - 1, 0)')
                    ]);
                }

                $newCount = $room->fresh()->current_occupancy;
                broadcast(new \App\Events\RoomOccupancyUpdated($uuid, $newCount));
            }
        }
    }
}