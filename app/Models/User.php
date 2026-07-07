<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Redis; // Добавь это!
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

    /**
     * Проверка онлайна в реальном времени через Redis
     */
public function isOnline(): bool
{
    // 1. Проверяем мгновенный след в Redis
    $redisLastSeen = \Illuminate\Support\Facades\Redis::hget('users_last_seen', $this->id);
    if ($redisLastSeen) {
        return (time() - (int)$redisLastSeen) < 100; 
    }

    // 2. Если в Redis нет (например, после очистки), проверяем базу.
    // Сравниваем Unix-метки, чтобы избежать бага "5 часов назад"
    return $this->last_seen && (time() - $this->last_seen->timestamp) < 100;
}

public function getLastSeenForHumans(): string
{
    if ($this->isOnline()) return 'В сети';
    if (!$this->last_seen) return 'Давно';

    // Форсируем сравнение с текущим моментом времени
    return $this->last_seen->diffForHumans();
}

    // Вспомогательные методы для UI (оставь как были)
    public function getXpProgressAttribute(): int { return ($this->xp % 1000) / 10; }
    public function getNextLevelXpAttribute(): int { return 1000; }
    public function getCurrentLevelXpAttribute(): int { return $this->xp % 1000; }
}