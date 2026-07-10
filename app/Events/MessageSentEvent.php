<?php

namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    // Свойство доступно для чтения (для вещания), но изменить его нельзя
    public private(set) array $messageData;

    public function __construct(array $messageData)
    {
        $this->messageData = $messageData;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->messageData['receiver_id']}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSentEvent';
    }
}