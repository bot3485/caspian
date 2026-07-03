<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Redis};
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            // Вместо БД пишем в Redis Hash с именем 'users_last_seen'
            // Поле: ID пользователя, Значение: текущая метка времени
            Redis::hset('users_last_seen', Auth::id(), now()->toDateTimeString());
        }

        return $next($request);
    }
}