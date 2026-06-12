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

            // ОТПРАВЛЯЕМ WebSocket-события правильно:
            // 1. Отправляем ПАРТНЕРУ (partnerId), что его собеседник — МЫ (userId)
            broadcast(new MatchFoundEvent($partnerId, $userId));

            // 2. Отправляем НАМ (userId), что наш собеседник — ПАРТНЕР (partnerId)
            broadcast(new MatchFoundEvent($userId, $partnerId));

            return response()->json([
                'status' => 'matched',
                'partnerId' => $partnerId
            ]);
        }

        // Если никого нет, добавляем текущего пользователя в queue ожидания
        Matchmaking::updateOrCreate(
            ['user_id' => $userId],
            ['status' => 'waiting']
        );

        return response()->json([
            'status' => 'searching'
        ]);
    }

    // Метод для ретрансляции WebRTC сигналов (SDP и ICE)
    public function sendSignal(Request $request)
    {
        $request->validate([
            'partnerId' => 'required|integer',
            'data' => 'required|array'
        ]);

        broadcast(new \App\Events\WebRTCSignalEvent($request->partnerId, $request->data))->toOthers();

        return response()->json(['status' => 'signal_sent']);
    }
}