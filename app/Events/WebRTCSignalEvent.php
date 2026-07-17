<?php
namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WebRTCSignalEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $targetUserId, public array $data) {}

    public function broadcastOn(): array {
        return [new PrivateChannel("user.{$this->targetUserId}")];
    }

    public function broadcastAs(): string {
        return 'WebRTCSignalEvent'; // Без точек и префиксов
    }
}