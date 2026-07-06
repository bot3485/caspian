<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Actions\FindPartner;
use App\Actions\LeaveChat;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;

class ChatController extends Controller
{
    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // 1. Сначала ПОЛНАЯ очистка (удаляем старые записи и партнерские связи)
        app(LeaveChat::class)->execute($userId);

        // 2. ФИКСИРУЕМ в БД, что мы реально ищем (статус searching)
        Matchmaking::create([
            'user_id' => $userId,
            'status' => MatchmakingStatus::Searching
        ]);

        // 3. Запускаем поиск партнера
        $finder = new FindPartner();
        $partnerId = $finder->execute($userId);

        if ($partnerId) {
            return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
        }

        return response()->json(['status' => 'searching']);
    }

    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partnerId' => 'required|integer',
            'data' => 'required|array'
        ]);

        $senderId = Auth::id();
        $receiverId = (int)$validated['partnerId'];
        $data = $validated['data'];
        $data['from'] = $senderId;

        // Разрешаем сигналы только если в Redis или MySQL есть подтвержденная пара
        $isAllowed = Redis::get("allow_signal:{$senderId}:{$receiverId}") || 
                     Matchmaking::where('user_id', $senderId)->where('partner_id', $receiverId)->exists();

        if (!$isAllowed) {
            if (isset($data['type']) && in_array($data['type'], ['hang-up', 'peer-disconnected'])) {
                return response()->json(['status' => 'ignored']);
            }
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        broadcast(new WebRTCSignalEvent($receiverId, $data));
        return response()->json(['status' => 'signal_sent']);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        app(LeaveChat::class)->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

    // Вспомогательные методы
    public function getContacts(): JsonResponse { 
        $contacts = \Illuminate\Support\Facades\DB::table('contacts')->where('contacts.user_id', Auth::id())->join('users', 'users.id', '=', 'contacts.contact_id')->select('users.id', 'users.name', 'users.last_seen')->get();
        return response()->json(['contacts' => $contacts]);
    }
    public function toggleContact(Request $request): JsonResponse {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $exists = \Illuminate\Support\Facades\DB::table('contacts')->where('user_id', Auth::id())->where('contact_id', $request->contactId)->exists();
        if ($exists) { \Illuminate\Support\Facades\DB::table('contacts')->where('user_id', Auth::id())->where('contact_id', $request->contactId)->delete(); return response()->json(['action' => 'removed']); }
        \Illuminate\Support\Facades\DB::table('contacts')->insert(['user_id' => Auth::id(), 'contact_id' => $request->contactId, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['action' => 'added']);
    }
    public function getChatHistory(Request $request, int $contactId): JsonResponse {
        $messages = \Illuminate\Support\Facades\DB::table('messages')->where(function($q) use ($contactId) { $q->where('sender_id', Auth::id())->where('receiver_id', $contactId); })->orWhere(function($q) use ($contactId) { $q->where('sender_id', $contactId)->where('receiver_id', Auth::id()); })->orderBy('id', 'desc')->take(30)->get()->reverse()->values();
        return response()->json(['messages' => $messages, 'has_more' => false]);
    }
    public function callContact(Request $request): JsonResponse {
        broadcast(new WebRTCSignalEvent($request->contactId, ['type' => 'incoming-direct-call', 'callerId' => Auth::id(), 'callerName' => Auth::user()->name, 'from' => Auth::id()]));
        return response()->json(['status' => 'calling']);
    }
    public function sendTypingSignal(Request $request): JsonResponse {
        broadcast(new WebRTCSignalEvent($request->receiver_id, ['type' => 'typing', 'from' => Auth::id()]));
        return response()->json(['status' => 'ok']);
    }
    public function sendMessage(Request $request): JsonResponse {
        $msg = ['sender_id' => Auth::id(), 'receiver_id' => $request->receiver_id, 'message' => $request->message, 'created_at' => now(), 'updated_at' => now()];
        $msg['id'] = \Illuminate\Support\Facades\DB::table('messages')->insertGetId($msg);
        $msg['created_at'] = now()->toIso8601String();
        broadcast(new \App\Events\MessageSentEvent($msg))->toOthers();
        return response()->json(['status' => 'sent', 'message' => $msg]);
    }
}