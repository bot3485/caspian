<?php

namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSentEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public array $messageData) {}

    public function broadcastOn(): array {
        return [new PrivateChannel("user.{$this->messageData['receiver_id']}")];
    }

    public function broadcastAs(): string {
        return 'MessageSentEvent';
    }

    /**
     * Определить, какие данные должны быть переданы в сокет.
     */
    public function broadcastWith(): array
    {
        return [
            'messageData' => [
                'id' => $this->messageData['id'] ?? null,
                // Если sender_id пустой в массиве, берем ID текущего авторизованного юзера
                'sender_id' => auth()->user()->hashid, // Хеш вместо цифры
                'receiver_id' => $this->messageData['receiver_id'], // Здесь уже должен быть хеш из контроллера
                'message' => $this->messageData['message'],
                'created_at' => $this->messageData['created_at'] ?? now()->toIso8601String(),
            ]
        ];
    }
}