<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Http; // Не забудьте импортировать Http
use Stevebauman\Location\Facades\Location;

class UpdateLastSeen
{
    public function handle(Request $request, Closure $next)
    {
        try {
            if (Auth::check()) {
                $user = Auth::user();
                $ip = $request->ip();

                // 1. Быстрое обновление статуса онлайн в Redis (каждый запрос)
                Redis::hset('users_last_seen', $user->id, now()->timestamp);

                // 2. Блокируем пустые IP
                if (empty($ip)) {
                    return $next($request);
                }

                // 3. Логика срабатывает ТОЛЬКО если IP изменился
                if ($user->last_ip !== $ip) {
                    
                    // А. Получаем Гео-позицию
                    $position = Location::get($ip);
                    $countryCode = 'us'; // Дефолт
                    
                    if ($position && !empty($position->countryCode)) {
                        $code = strtolower($position->countryCode);
                        if (\App\Enums\UserCountry::tryFrom($code)) {
                            $countryCode = $code;
                        }
                    }

                    // Б. Проверка на VPN (proxycheck.io)
                    $isVpn = false;
                    try {
                        $apiKey = config('services.proxycheck.key');
                        // Таймаут 2 сек, чтобы не вешать загрузку страницы если API тормозит
                        $vpnResponse = Http::timeout(2)->get("https://proxycheck.io/v2/{$ip}?key={$apiKey}&vpn=1")->json();

                        if (isset($vpnResponse[$ip])) {
                            $isVpn = ($vpnResponse[$ip]['proxy'] === 'yes');
                        }
                    } catch (\Exception $e) {
                        Log::error("ProxyCheck Error: " . $e->getMessage());
                    }

                    // В. ОДИН запрос в базу данных на обновление всех полей
                    $user->update([
                        'country_code' => $countryCode,
                        'last_ip'      => $ip,
                        'is_vpn'       => $isVpn
                    ]);
                    
                    Log::info("User {$user->id} updated IP to {$ip}. VPN: " . ($isVpn ? 'YES' : 'NO'));
                }
            }
        } catch (\Exception $e) {
            Log::error('Middleware UpdateLastSeen critical error: ' . $e->getMessage());
        }

        // КРИТИЧЕСКИ ВАЖНО: всегда возвращаем $next($request)
        return $next($request);
    }
}