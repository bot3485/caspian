<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Events\MatchFoundEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function startSearching(Request $request)
    {
        $user = $request->user();

        // 1. Пытаемся найти другого пользователя в очереди
        $opponent = Matchmaking::where('status', 'searching')
            ->where('user_id', '!=', $user->id)
            ->first();

        if ($opponent) {
            // Мэтч найден!
            $opponent->update(['status' => 'matched']);
            
            // Отправляем события
            broadcast(new MatchFoundEvent($user->id, $opponent->user_id));
            broadcast(new MatchFoundEvent($opponent->user_id, $user->id));
            
            return response()->json(['status' => 'matched', 'partner_id' => $opponent->user_id]);
        }

        // 2. Никого нет — встаем в очередь
        Matchmaking::updateOrCreate(
            ['user_id' => $user->id], 
            ['status' => 'searching']
        );
        
        return response()->json(['status' => 'searching']);
    }
}