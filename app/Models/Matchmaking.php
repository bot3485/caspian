<?php

namespace App\Models;

use App\Enums\MatchmakingStatus;
use Illuminate\Database\Eloquent\Model;

class Matchmaking extends Model
{
    protected $table = 'matchmaking_queue';

    protected $fillable = ['user_id', 'status', 'partner_id'];

    // Оставляем только автоматическое преобразование в Enum
    protected function casts(): array
    {
        return [
            'status' => MatchmakingStatus::class,
        ];
    }
}