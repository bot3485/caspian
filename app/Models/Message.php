<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\{Fillable, Casts};

#[Fillable(['sender_id', 'receiver_id', 'message', 'is_read'])]
#[Casts(['is_read' => 'boolean'])]
class Message extends Model
{
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    /**
     * PHP 8.4 Property Hook
     * Автоматически очищает текст сообщения от лишних пробелов и HTML тегов.
     */
    public string $message {
        get => $this->attributes['message'] ?? '';
        set => $this->attributes['message'] = trim(strip_tags($value));
    }
}