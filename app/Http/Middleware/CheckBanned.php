<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckBanned
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        if (auth()->check() && auth()->user()->banned_until && now()->lessThan(auth()->user()->banned_until)) {
            $banned_until = auth()->user()->banned_until->format('d.m.Y H:i');
            
            // Разлогиниваем пользователя
            auth()->logout();

            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Отправляем на страницу логина с текстом ошибки
            return redirect()->route('login')->withErrors([
                'email' => "Your account has been suspended until {$banned_until}. Contact support for details.",
            ]);
        }

        return $next($request);
    }
}
