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
            $user = Auth::user();
            $lastUpdate = Session::get('last_seen_update', 0);
            $now = time();

            if ($now - $lastUpdate > 60) { // Раз в минуту
                Redis::hset('users_last_seen', $user->id, $now);
                $user->increment('site_minutes'); // Считаем каждую минуту на сайте
                Session::put('last_seen_update', $now);
            }
        }
        return $next($request);
    }
}