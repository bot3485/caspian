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
            // Пишем просто текущий Timestamp (секунды)
            \Illuminate\Support\Facades\Redis::hset('users_last_seen', Auth::id(), time());
        }
        return $next($request);
    }
}