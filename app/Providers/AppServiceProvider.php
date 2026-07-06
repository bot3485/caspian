<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\LeaveChat;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen(PresenceChannelMemberLeft::class, function (PresenceChannelMemberLeft $event) {
            if ($event->channel->name === 'presence-online-status') {
                $userId = $event->user->id;
                
                // Находим с кем он был в паре ПЕРЕД удалением
                $match = \App\Models\Matchmaking::where('user_id', $userId)->first();
                
                if ($match && $match->partner_id) {
                    // Мгновенно шлем сигнал партнеру от имени системы
                    broadcast(new \App\Events\WebRTCSignalEvent($match->partner_id, [
                        'type' => 'peer-disconnected',
                        'from' => $userId, // Кто ушел
                        'reason' => 'connection_lost'
                    ]));
                }

                app(\App\Actions\LeaveChat::class)->execute($userId);
            }
        });
    }
}
