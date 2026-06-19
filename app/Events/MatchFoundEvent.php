<?php

namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFoundEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * PHP 8.4: Асимметричная видимость (Asymmetric Visibility).
     * Свойство доступно для чтения отовсюду (public), 
     * но изменять его можно только внутри класса (private set).
     */
    public private(set) int $partnerId;
    private int $targetUserId;

    public function __construct(int $targetUserId, int $partnerId)
    {
        $this->targetUserId = $targetUserId;
        $this->partnerId = $partnerId;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->targetUserId}"),
        ];
    }

    // Исправлено: методы всегда требуют {} и return
    public function broadcastAs(): string
    {
        return 'MatchFoundEvent';
    }
}