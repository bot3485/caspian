<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use App\Events\TestEvent;
use Illuminate\Support\Facades\Broadcast;
Route::get('/', function () {
    return view('welcome');
});




Route::get('/debug-broadcast', function () {
    try {
        // Проверяем, что драйвер вообще доступен
        $driver = config('broadcasting.default');
        Log::info("Debug Broadcast: Driver is $driver");

        // Пытаемся отправить
        event(new \App\Events\TestEvent());
        
        return "Событие отправлено успешно!";
    } catch (\Exception $e) {
        // Возвращаем текст ошибки в браузер, чтобы не гадать
        return "Ошибка сервера: " . $e->getMessage() . "\n" . $e->getTraceAsString();
    }
});
