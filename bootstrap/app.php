<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use App\Http\Middleware\UpdateLastSeen;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Здесь можно добавить глобальные Middleware в новом стиле
        $middleware->statefulApi(); // Если планируете использовать Sanctum
        $middleware->web(append: [
        \App\Http\Middleware\UpdateLastSeen::class,
    ]);
    })
->withExceptions(function (Exceptions $exceptions) {
    // Игнорируем это тупое уведомление PHP 8.5 про временную папку
    $exceptions->reportable(function (\ErrorException $e) {
        if (str_contains($e->getMessage(), 'tempnam()')) {
            return false; 
        }
    });

    $exceptions->shouldRenderJsonWhen(
        fn (Request $request) => $request->is('api/*') || $request->expectsJson()
    );
    })->create();