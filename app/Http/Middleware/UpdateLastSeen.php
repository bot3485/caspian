<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Stevebauman\Location\Facades\Location;

class UpdateLastSeen
{
public function handle(Request $request, Closure $next)
{
    try {
        if (Auth::check()) {
            $user = Auth::user();
            $ip = $request->ip();

            // 1. Быстрое обновление статуса онлайн в Redis
            Redis::hset('users_last_seen', $user->id, now()->timestamp);

            // 2. Блокируем пустые IP (защита от прокси/балансировщиков)
            if (empty($ip)) {
                return $next($request);
            }

            // 3. Обновляем GeoIP и пишем в БД ТОЛЬКО если IP реально изменился
            if ($user->last_ip !== $ip) {
                $position = Location::get($ip);
                
                $countryCode = 'us'; // Дефолт на случай неудачи

                // БЕЗОПАСНАЯ ПРОВЕРКА: убеждаемся, что position есть и код страны не null
                if ($position && !empty($position->countryCode)) {
                    $code = strtolower($position->countryCode);
                    
                    // Проверяем, поддерживает ли наш Enum этот код
                    if (\App\Enums\UserCountry::tryFrom($code)) {
                        $countryCode = $code;
                    }
                }

                // Сохраняем локацию и IP
                $user->update([
                    'country_code' => $countryCode,
                    'last_ip' => $ip
                ]);
            }
        }
    } catch (\Exception $e) {
        // Логируем ошибку, чтобы ничего не падало
        Log::error('Middleware UpdateLastSeen error: ' . $e->getMessage());
    }

    return $next($request);
}
}