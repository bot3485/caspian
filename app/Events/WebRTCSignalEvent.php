<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class WebRTCSignalEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $partnerId,
        public array $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->partnerId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WebRTCSignalEvent';
    }
}