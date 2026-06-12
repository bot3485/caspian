<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Events\MatchFoundEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ChatController extends Controller
{
    public function startSearching(Request $request)
    {
        $userId = Auth::id();
        Log::info("Пользователь {$userId} начал поиск партнера.");

        // Ищем пользователя, который уже ждет в очереди
        $waitingUser = Matchmaking::where('user_id', '!=', $userId)
            ->where('status', 'waiting')
            ->first();

        if ($waitingUser) {
            $partnerId = $waitingUser->user_id;

            // Обновляем статусы в базе данных
            $waitingUser->update(['status' => 'matched']);
            Matchmaking::create([
                'user_id' => $userId,
                'status' => 'matched'
            ]);

            Log::info("Мэтч найден! Пара: User {$userId} и User {$partnerId}");

            // Отправляем WebSocket-события обоим участникам
            broadcast(new MatchFoundEvent($partnerId));
            broadcast(new MatchFoundEvent($userId));

            return response()->json([
                'status' => 'matched',
                'partnerId' => $partnerId
            ]);
        }

        // Если никого нет, добавляем текущего пользователя в очередь ожидания
        Matchmaking::updateOrCreate(
            ['user_id' => $userId],
            ['status' => 'waiting']
        );

        return response()->json([
            'status' => 'searching'
        ]);
    }
}