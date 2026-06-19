<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Attributes\{Fillable, Hidden, Casts};

#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
#[Casts(['email_verified_at' => 'datetime', 'password' => 'hashed'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable;

    /**
     * PHP 8.4+ Property Hooks
     * Мы должны указать и get, и set, чтобы связать свойство с внутренним массивом Eloquent.
     */
    public string $name {
        // Читаем из массива атрибутов модели
        get => $this->attributes['name'] ?? ''; 
        // Очищаем от пробелов при записи
        set => $this->attributes['name'] = trim($value);
    }

    public string $email {
        get => $this->attributes['email'] ?? '';
        set => $this->attributes['email'] = strtolower(trim($value));
    }
}