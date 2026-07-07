<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    // Классический массив разрешенных полей
    protected $fillable = [
        'name', 'email', 'password', 'last_seen', 'banned_until', 
        'interests', 'karma', 'xp', 'level', 'total_minutes'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    // Классический метод приведения типов
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime', 
            'password' => 'hashed', 
            'last_seen' => 'datetime',
            'banned_until' => 'datetime',
            'xp' => 'integer', 
            'level' => 'integer',
            'interests' => 'array', // Гарантирует, что в БД будет JSON, а в коде массив
            'karma' => 'integer',
            'total_minutes' => 'integer'
        ];
    }

    public function matchmaking(): HasOne
    {
        return $this->hasOne(Matchmaking::class);
    }

    // Вспомогательные методы для UI
    public function getXpProgressAttribute(): int { return ($this->xp % 1000) / 10; }
    public function getNextLevelXpAttribute(): int { return 1000; }
    public function getCurrentLevelXpAttribute(): int { return $this->xp % 1000; }
}