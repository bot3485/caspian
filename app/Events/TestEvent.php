<?php
namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

public function broadcastOn(): Channel
    {
        return new \Illuminate\Broadcasting\Channel('test-channel');
    }
    
public function broadcastQueue(): string
    {
        return 'default';
    }
}

