<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Broadcasting\Events\PresenceChannelMemberJoined;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;
use App\Models\Room;
use App\Events\RoomOccupancyUpdated;

class AppServiceProvider extends ServiceProvider
{
        public function boot(): void
    {
        // Слушаем вход
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Broadcasting\Events\PresenceChannelMemberJoined::class, function ($event) {
            $this->syncRoomOccupancy($event->channel->name);
        });

        // Слушаем выход
        \Illuminate\Support\Facades\Event::listen(\Illuminate\Broadcasting\Events\PresenceChannelMemberLeft::class, function ($event) {
            $this->syncRoomOccupancy($event->channel->name);
        });
    }

    protected function syncRoomOccupancy($channelName)
    {
        // Извлекаем UUID (поддерживает форматы: presence-room.uuid, room.uuid и т.д.)
        if (preg_match('/room\.([a-f0-9\-]{36})/', $channelName, $matches)) {
            $uuid = $matches[1];
            
            $room = \App\Models\Room::where('uuid', $uuid)->first();
            if ($room) {
                // Обновляем реальное количество (здесь можно использовать Redis для точности)
                // Для начала просто обновим по факту события:
                $room->update(['current_occupancy' => \App\Models\Room::where('uuid', $uuid)->first()->current_occupancy]);
                
                // ВАЖНО: Если вы тестируете один, зайдите под разными аккаунтами!
                broadcast(new \App\Events\RoomOccupancyUpdated($uuid, $room->current_occupancy));
            }
        }
    }
}