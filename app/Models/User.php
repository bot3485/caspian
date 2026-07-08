<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis;
use Carbon\Carbon;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'last_seen', 'banned_until', 
        'interests', 'karma', 'xp', 'level', 'total_minutes', 'site_minutes'
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
        return match (true) {
            $this->level >= 50 => 'Legendary',
            $this->level >= 25 => 'Elite',
            $this->level >= 10 => 'Veteran',
            default => 'Explorer',
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
}