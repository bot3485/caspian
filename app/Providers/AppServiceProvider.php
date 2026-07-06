<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\LeaveChat;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;
use Illuminate\Support\Facades\Log;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        Event::listen(PresenceChannelMemberLeft::class, function (PresenceChannelMemberLeft $event) {
            // Исправлено: проверяем окончание имени канала (Laravel добавляет префикс 'presence-')
            if (str_ends_with($event->channel->name, 'online-status')) {
                $userId = $event->user->id;
                
                $match = \App\Models\Matchmaking::where('user_id', $userId)->first();
                
                if ($match && $match->partner_id) {
                    broadcast(new \App\Events\WebRTCSignalEvent($match->partner_id, [
                        'type' => 'peer-disconnected',
                        'from' => $userId,
                        'reason' => 'connection_lost'
                    ]));
                }

                app(LeaveChat::class)->execute($userId);
            }
        });
    }
}