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

                // 1. Быстрое обновление статуса онлайн в Redis (Единый источник истины для онлайна)
                // Команда SyncLastSeen заберет эти данные и положит в БД по расписанию.
                Redis::hset('users_last_seen', $user->id, now()->timestamp);

                // 2. Обновляем GeoIP и пишем в БД ТОЛЬКО если IP реально изменился
                if ($user->last_ip !== $ip) {
                    $position = Location::get($ip);
                    
                    $countryCode = ($position && \App\Enums\UserCountry::tryFrom(strtolower($position->countryCode))) 
                                    ? strtolower($position->countryCode) 
                                    : 'us';

                    // Сохраняем новую локацию в БД (выполняется редко, не грузит систему)
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