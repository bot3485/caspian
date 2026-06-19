<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    /**
     * Обработка входящего запроса.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Обновляем дату последней активности
            // Используем quiet-обновление, чтобы не срабатывали события модели (если не нужно)
            Auth::user()->fill(['last_seen' => now()])->saveQuietly();
        }

        return $next($request);
    }
}