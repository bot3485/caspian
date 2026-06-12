<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Matchmaking extends Model
{
    protected $table = 'matchmaking_queue';
    
    protected $fillable = ['user_id', 'status'];

}
