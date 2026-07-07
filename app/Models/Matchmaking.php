<?php

namespace App\Models;

use App\Enums\MatchmakingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Matchmaking extends Model
{
    protected $table = 'matchmaking_queue';

    protected $fillable = ['user_id', 'status', 'partner_id'];

    // КРИТИЧНО: Добавляем связь, чтобы поиск по времени (last_seen) работал
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected function casts(): array
    {
        return [
            'status' => MatchmakingStatus::class,
        ];
    }
}