<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;
use Hashids\Hashids;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'last_seen', 'banned_until', 
        'interests', 'karma', 'xp', 'level', 'total_minutes', 'site_minutes',
        'country_code', 'target_country', 'locale', 'last_ip', 'is_vpn',
        'gender', 'age', 'target_gender', 'target_age_min', 'target_age_max'
        ];

    protected $casts = [
        'email_verified_at' => 'datetime', 
        'password' => 'hashed', 
        'last_seen' => 'datetime',
        'banned_until' => 'datetime',
        'interests' => 'array',
    ];

    // --- ДИНАМИЧЕСКИЕ АТРИБУТЫ (REVITALIZATION) ---

    /**
     * Название ранга на основе уровня.
     */
    public function getRankNameAttribute(): string
    {
        if ($this->karma < 20) return 'Shadowed'; // Для нарушителей
        
        return match (true) {
            $this->level >= 100 => 'Grandmaster',
            $this->level >= 50  => 'Legendary',
            $this->level >= 25  => 'Elite',
            $this->level >= 10  => 'Veteran',
            $this->level >= 5   => 'Regular',
            default => 'Newbie',
        };
    }

    /**
     * Премиум статус на основе кармы (информативность для меню).
     */
    public function getIsPremiumAttribute(): bool
    {
        return $this->karma >= 200;
    }

    /**
     * Процент прогресса до следующего уровня.
     */
    public function getXpProgressAttribute(): int 
    { 
        return (int) (($this->xp % 1000) / 10); 
    }

    // --- СТАТУС ОНЛАЙН ---

    public function isOnline(): bool
    {
        $redisLastSeen = Redis::hget('users_last_seen', $this->id);
        if ($redisLastSeen) {
            return (time() - (int)$redisLastSeen) < 100; 
        }
        return $this->last_seen && (time() - $this->last_seen->timestamp) < 100;
    }

    public function getLastSeenForHumans(): string
    {
        if ($this->isOnline()) return 'В сети';
        if (!$this->last_seen) return 'Давно';
        return $this->last_seen->diffForHumans();
    }

    public function getNextLevelXpAttribute(): int { return 1000; }
    public function getCurrentLevelXpAttribute(): int { return $this->xp % 1000; }

    public function getStatusDataAttribute(): array
    {
        $isOnline = $this->isOnline();
        return [
            'is_online' => $isOnline,
            'label' => $isOnline ? 'В сети' : $this->getLastSeenForHumans(),
            'color' => $isOnline ? '#22c55e' : '#6b7280',
            // В 2026 году мы можем определять устройство по User-Agent в сессии
            'device' => str_contains(request()->header('User-Agent'), 'Mobile') ? 'mobile' : 'desktop'
        ];
    }

public function getPrestigeBadgeAttribute(): array
{
    $m = $this->site_minutes;

    return match (true) {
        $m >= 100000 => ['name' => 'Celestial', 'icon' => '⚛️', 'color' => '#ffffff', 'glow' => '0 0 20px #ffffff'], 
        $m >= 50000  => ['name' => 'Eternal',   'icon' => '🌌', 'color' => '#ef4444', 'glow' => '0 0 15px #ef4444'], 
        $m >= 25000  => ['name' => 'Imperial',  'icon' => '💎', 'color' => '#f59e0b', 'glow' => '0 0 12px #f59e0b'],
        $m >= 10000  => ['name' => 'Overlord',  'icon' => '🔱', 'color' => '#ec4899', 'glow' => 'none'],
        $m >= 5000   => ['name' => 'Commander', 'icon' => '🎖️', 'color' => '#a855f7', 'glow' => 'none'],
        $m >= 2500   => ['name' => 'Veteran',   'icon' => '🛡️', 'color' => '#6366f1', 'glow' => 'none'],
        $m >= 1000   => ['name' => 'Resident',  'icon' => '🏠', 'color' => '#3b82f6', 'glow' => 'none'],
        $m >= 500    => ['name' => 'Nomad',     'icon' => '🧭', 'color' => '#2dd4bf', 'glow' => 'none'],
        $m >= 100    => ['name' => 'Explorer',  'icon' => '🛰️', 'color' => '#10b981', 'glow' => 'none'],
        default      => ['name' => 'Guest',     'icon' => '🐚', 'color' => '#94a3b8', 'glow' => 'none'],
    };
}

public function getHashidAttribute()
{
    $hashids = new Hashids(config('app.key'), 10);
    return $hashids->encode($this->id);
}

}