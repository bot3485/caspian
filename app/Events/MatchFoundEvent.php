<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFoundEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    private $targetUserId;
    public $partnerId;

    // Конструктор принимает: 1. Кому доставить, 2. ID собеседника для фронтенда
    public function __construct($targetUserId, $partnerId)
    {
        $this->targetUserId = $targetUserId;
        $this->partnerId = $partnerId;
    }

    public function broadcastOn(): array
    {
        // Отправляем строго в приватный канал конкретного получателя
        return [
            new PrivateChannel('user.' . $this->targetUserId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MatchFoundEvent';
    }
}