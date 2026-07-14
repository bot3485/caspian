<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\UpdateLastSeen;
use App\Http\Middleware\SetLocaleMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Подключаем API с сохранением состояния (для Sanctum, если потребуется)
        $middleware->statefulApi();

        // Регистрируем Middleware для обновления времени посещения и установки локали
        $middleware->web(append: [
            UpdateLastSeen::class,
            SetLocaleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Игнорируем специфичное для PHP 8.5 предупреждение про временную папку
        $exceptions->reportable(function (\ErrorException $e) {
            if (str_contains($e->getMessage(), 'tempnam()')) {
                return false; 
            }
        });

        // Корректно возвращаем JSON при запросах к API или при ожиданиях JSON-ответа
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson()
        );
    })->create();