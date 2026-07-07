<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Attributes\{Fillable, Hidden, Casts};
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'email', 'password', 'last_seen', 'banned_until', 'interests', 'karma', 'xp', 'level', 'total_minutes'])]
#[Hidden(['password', 'remember_token'])]
#[Casts([
    'email_verified_at' => 'datetime', 
    'password' => 'hashed', 
    'last_seen' => 'datetime',
    'banned_until' => 'datetime',
    'xp' => 'integer', 
    'level' => 'integer',
    'interests' => 'array', 
    'karma' => 'integer',
    'total_minutes' => 'integer'
])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public function matchmaking(): HasOne
    {
        return $this->hasOne(Matchmaking::class);
    }

    // Хук для имени
    public string $name {
        get => $this->attributes['name'] ?? ''; 
        set => $this->attributes['name'] = trim($value);
    }

    // Хук для email
    public string $email {
        get => $this->attributes['email'] ?? '';
        set => $this->attributes['email'] = strtolower(trim($value));
    }

    /**
     * НОВЫЙ ХУК ДЛЯ ИНТЕРЕСОВ (PHP 8.4)
     * Этот хук будет автоматически выдавать готовую строку для инпута
     */
    public string $interests_as_string {
        get {
            $val = $this->interests;
            if (is_array($val)) {
                return implode(', ', $val);
            }
            // Если в базе лежит строка (бывает при ошибках), пробуем декодировать
            $decoded = json_decode($this->attributes['interests'] ?? '[]', true);
            return is_array($decoded) ? implode(', ', $decoded) : '';
        }
    }

    public function getXpProgressAttribute(): int { return ($this->xp % 1000) / 10; }
    public function getNextLevelXpAttribute(): int { return 1000; }
    public function getCurrentLevelXpAttribute(): int { return $this->xp % 1000; }
}