<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Room extends Model
{
    // Классический способ - самый надежный для Laravel 13 сейчас
    protected $fillable = ['uuid', 'title', 'password', 'creator_id', 'is_public'];

    protected function casts(): array
    {
        return [
            'is_public' => 'boolean',
            'password' => 'hashed', // Авто-хеширование Laravel
        ];
    }

    protected static function booted(): void
    {
        static::creating(fn (Room $room) => $room->uuid = (string) Str::uuid());
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}