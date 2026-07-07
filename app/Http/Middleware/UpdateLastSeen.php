<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Redis, Session};
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $lastUpdate = Session::get('last_seen_update', 0);
            $now = time();

            // v1.9: Обновляем статус не чаще чем раз в 30 секунд
            if ($now - $lastUpdate > 30) {
                Redis::hset('users_last_seen', Auth::id(), $now);
                Session::put('last_seen_update', $now);
            }
        }
        return $next($request);
    }
}