<?php

namespace App\Models;

use App\Enums\MatchmakingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\{Fillable, Casts};

#[Fillable(['user_id', 'status', 'partner_id'])] // Добавили partner_id
#[Casts(['status' => MatchmakingStatus::class])] // Авто-преобразование строки в Enum
class Matchmaking extends Model
{
    protected $table = 'matchmaking_queue';

    /**
     * PHP 8.4 Property Hooks
     * Теперь работаем с объектом Enum, а не со строкой
     */
    public MatchmakingStatus $status {
        get => $this->attributes['status'] instanceof MatchmakingStatus 
               ? $this->attributes['status'] 
               : MatchmakingStatus::from($this->attributes['status']);
               
        set(MatchmakingStatus $value) => $this->attributes['status'] = $value->value;
    }
}