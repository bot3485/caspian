<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Actions\LeaveChat;
use Illuminate\Support\Facades\Event;
use Illuminate\Broadcasting\Events\PresenceChannelMemberLeft;
use App\Events\WebRTCSignalEvent;
use App\Models\Matchmaking;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void {}

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Логика автоматической очистки при обрыве связи (Presence Channel)
        Event::listen(PresenceChannelMemberLeft::class, function (PresenceChannelMemberLeft $event) {
            // Проверяем, что пользователь покинул именно канал статуса
            if (str_ends_with($event->channel->name, 'online-status')) {
                $userId = $event->user->id;
                
                // Находим, был ли пользователь в активном чате
                $match = Matchmaking::where('user_id', $userId)->first();
                
                if ($match && $match->partner_id) {
                    // Уведомляем собеседника, что связь прервана
                    broadcast(new WebRTCSignalEvent($match->partner_id, [
                        'type' => 'peer-disconnected',
                        'from' => $userId,
                        'reason' => 'connection_lost'
                    ]));
                }

                // Выполняем экшен выхода (удаление из очереди и БД)
                app(LeaveChat::class)->execute($userId);
            }
        });
    }
}