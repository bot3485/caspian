<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
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
        return [new Channel('rooms-lobby')];
    }

    public function broadcastAs(): string
    {
        return 'OccupancyUpdated';
    }
}