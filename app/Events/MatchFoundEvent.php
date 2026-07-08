<?php

namespace App\Events;

use Illuminate\Broadcasting\{InteractsWithSockets, PrivateChannel};
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MatchFoundEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * PHP 8.4: Asymmetric Visibility.
     * partnerData contains: id, name, level, rank_name, karma, interests
     */
    public private(set) array $partnerData;
    public private(set) bool $isFriend;
    private int $targetUserId;

    public function __construct(int $targetUserId, array $partnerData, bool $isFriend = false)
    {
        $this->targetUserId = $targetUserId;
        $this->partnerData = $partnerData;
        $this->isFriend = $isFriend;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("user.{$this->targetUserId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MatchFoundEvent';
    }
}