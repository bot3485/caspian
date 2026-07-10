<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Мы удалили отсюда Event::listen для Presence каналов.
        // Теперь счетчик комнат (current_occupancy) обновляется 
        // через RoomController@syncOccupancy по сигналу от клиента.
        // Это предотвращает баг 0/6 при перезагрузке страницы.
    }
}