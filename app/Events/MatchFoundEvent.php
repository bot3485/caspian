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

    public $partnerId;
    public $myId;

    public function __construct($myId, $partnerId)
    {
        $this->myId = $myId;
        $this->partnerId = $partnerId;
    }

    public function broadcastOn(): Channel
    {
        // ВАЖНО: Мы используем приватный канал пользователя, 
        // чтобы сообщение пришло только конкретному человеку
        new PrivateChannel('user.' . $this->partnerId);
    }
}
