<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // Должно быть Now
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RoomOccupancyUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $roomUuid,
        public int $count
    ) {}

    public function broadcastOn(): array
    {
        // Канал должен быть публичным (Channel), а не Private
        return [new Channel('rooms-lobby')];
    }

    public function broadcastAs(): string
    {
        // Имя события для Echo (с точкой на фронте .OccupancyUpdated)
        return 'OccupancyUpdated';
    }
}