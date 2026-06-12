<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow; // <-- Изменение здесь
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcastNow // <-- И здесь
{
    use Dispatchable, SerializesModels;

public function broadcastOn(): \Illuminate\Broadcasting\Channel
{
    // Используем именно публичный Channel
    return new \Illuminate\Broadcasting\Channel('test-channel');
}
    
    public function broadcastQueue(): string
    {
        return 'default';
    }

    public function broadcastAs(): string
{
    return 'test-event';
}
}
