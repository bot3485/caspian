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
        // Слушаем событие выхода из Presence канала
        Event::listen(PresenceChannelMemberLeft::class, function (PresenceChannelMemberLeft $event) {
            // Если пользователь ушел из глобального канала статуса или комнаты
            if ($event->channel->name === 'presence-online-status') {
                // Важно: Reverb передает объект пользователя в $event->user
                $userId = $event->user->id;
                
                // Запускаем наш Action очистки
                app(LeaveChat::class)->execute($userId);
            }
        });
    }
}
