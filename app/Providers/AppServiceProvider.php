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
        // 1. Ищем UUID комнаты в названии канала
        if (preg_match('/room\.([a-f0-9\-]{36})/', $channelName, $matches)) {
            $uuid = $matches[1];
            $room = Room::where('uuid', $uuid)->first();
            
            if ($room) {
                // 2. Обновляем базу данных
                if ($action === 'joined') {
                    $room->increment('current_occupancy');
                } else {
                    if ($room->current_occupancy > 0) {
                        $room->decrement('current_occupancy');
                    }
                }

                $newCount = $room->fresh()->current_occupancy;

                // 3. ОТПРАВЛЯЕМ СОБЫТИЕ В ЛОББИ (этого ждут карточки комнат)
                // Убедитесь, что класс события RoomOccupancyUpdated принимает (uuid, count)
                broadcast(new \App\Events\RoomOccupancyUpdated($uuid, $newCount));
            }
        }
    }
}