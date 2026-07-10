<?php
namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserTypingEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public int $receiverId, public int $senderId) {}

    public function broadcastOn(): array {
        return [new PrivateChannel("user.{$this->receiverId}")];
    }

    public function broadcastAs(): string {
        return 'UserTypingEvent';
    }
}