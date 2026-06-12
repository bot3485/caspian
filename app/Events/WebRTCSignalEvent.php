<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class WebRTCSignalEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $partnerId;
    public $data;

    public function __construct($partnerId, $data)
    {
        $this->partnerId = $partnerId;
        $this->data = $data; // Тут будут сидеть sdp-пакеты и ice-кандидаты
    }

    public function broadcastOn(): array
    {
        // Вещаем в приватный канал того пользователя, КОМУ предназначены сигналы
        return [
            new PrivateChannel('user.' . $this->partnerId),
        ];
    }

    public function broadcastAs(): string
    {
        return 'WebRTCSignalEvent';
    }
}