<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'status'])]
class Matchmaking extends Model
{
    protected $table = 'matchmaking_queue';

    public string $status {
        // Обязательно добавляем проверку на существование ключа в массиве
        get => ucfirst($this->attributes['status'] ?? '');
        set(string $value) => $this->attributes['status'] = strtolower($value);
    }
}