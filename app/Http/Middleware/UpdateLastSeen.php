<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Stevebauman\Location\Facades\Location;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            $user = Auth::user();
            $ip = $request->ip();

            // 1. Обновляем время последнего визита
            $user->last_seen = now();

            // 2. Логика динамического определения страны по IP
            if ($ip) {
                if ($ip === '127.0.0.1' || $ip === 'localhost') {
                    // Если мы на локалке, эмулируем, что зашли под VPN Турции (или любой другой для локального теста)
                    if ($user->last_ip !== '127.0.0.1') {
                        $user->country_code = 'tr';
                        $user->last_ip = '127.0.0.1';
                    }
                } else {
                    // Если реальный IP-адрес пользователя изменился (включил VPN / сменил сеть)
                    if ($user->last_ip !== $ip) {
                        $position = Location::get($ip);
                        
                        if ($position) {
                            $countryCode = strtolower($position->countryCode);
                            
                            // Проверяем, поддерживает ли наш Enum эту страну
                            if (\App\Enums\UserCountry::tryFrom($countryCode)) {
                                $user->country_code = $countryCode;
                            } else {
                                // Если страны нет в Enum, пишем дефолтный 'us'
                                $user->country_code = 'us';
                            }
                        }
                        
                        // Запоминаем новый IP, чтобы не дергать GeoIP на каждый клик
                        $user->last_ip = $ip;
                    }
                }
            }

            // Сохраняем изменения в базе данных за один раз
            $user->save();
        }

        return $next($request);
    }
}