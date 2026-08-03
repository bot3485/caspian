<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\UpdateLastSeen;
use App\Http\Middleware\SetLocaleMiddleware;
use App\Http\Middleware\CheckBanned;
use App\Http\Middleware\ClearChatState;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->statefulApi();

        $middleware->alias([
            'clear.chat' => ClearChatState::class,
        ]);

        $middleware->web(append: [
            UpdateLastSeen::class,
            SetLocaleMiddleware::class,
            CheckBanned::class, 
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