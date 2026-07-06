<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Actions\FindPartner;
use App\Actions\LeaveChat;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;
use App\Events\MessageSentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function __construct(
        protected LeaveChat $leaveChatAction,
        protected FindPartner $findPartnerAction
    ) {}

    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $this->leaveChatAction->execute($userId);

        Matchmaking::create([
            'user_id' => $userId,
            'status' => MatchmakingStatus::Searching
        ]);

        $partnerId = $this->findPartnerAction->execute($userId);

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

        // Простая и надежная проверка доступа
        $isAllowed = Redis::exists("allow_signal:{$senderId}:{$receiverId}") || 
                     Redis::exists("allow_signal:{$receiverId}:{$senderId}") ||
                     Matchmaking::where('user_id', $senderId)->where('partner_id', $receiverId)->exists();

        if (!$isAllowed) {
            // Если это сигнал о разрыве, разрешаем его всегда для очистки
            if (isset($data['type']) && in_array($data['type'], ['hang-up', 'peer-disconnected'])) {
                broadcast(new WebRTCSignalEvent($receiverId, $data));
                return response()->json(['status' => 'disconnected_signal_sent']);
            }
            return response()->json(['error' => 'Unauthorized Match'], 403);
        }

        broadcast(new WebRTCSignalEvent($receiverId, $data));
        return response()->json(['status' => 'signal_sent']);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

    public function getContacts(): JsonResponse 
    { 
        $contacts = DB::table('contacts')
            ->where('contacts.user_id', Auth::id())
            ->join('users', 'users.id', '=', 'contacts.contact_id')
            ->select('users.id', 'users.name', 'users.last_seen')
            ->get();
        return response()->json(['contacts' => $contacts]);
    }

    public function toggleContact(Request $request): JsonResponse 
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $contactId = $request->contactId;
        $userId = Auth::id();

        $exists = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->exists();

        if ($exists) { 
            DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->delete(); 
            return response()->json(['action' => 'removed']); 
        }

        DB::table('contacts')->insert([
            'user_id' => $userId, 
            'contact_id' => $contactId, 
            'created_at' => now(), 
            'updated_at' => now()
        ]);
        return response()->json(['action' => 'added']);
    }

    public function getChatHistory(Request $request, int $contactId): JsonResponse 
    {
        $messages = DB::table('messages')
            ->where(function($q) use ($contactId) { 
                $q->where('sender_id', Auth::id())->where('receiver_id', $contactId); 
            })
            ->orWhere(function($q) use ($contactId) { 
                $q->where('sender_id', $contactId)->where('receiver_id', Auth::id()); 
            })
            ->orderBy('id', 'desc')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request): JsonResponse 
    {
        $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        $msg = [
            'sender_id' => Auth::id(), 
            'receiver_id' => $request->receiver_id, 
            'message' => $request->message, 
            'created_at' => now(), 
            'updated_at' => now()
        ];
        
        $msg['id'] = DB::table('messages')->insertGetId($msg);
        $msg['created_at'] = now()->toIso8601String();

        broadcast(new MessageSentEvent($msg))->toOthers();

        return response()->json(['status' => 'sent', 'message' => $msg]);
    }

    public function callContact(Request $request): JsonResponse 
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $contactId = (int)$request->contactId;
        $user = Auth::user();

        // Разрешаем сигналы между этими двумя пользователями на 5 минут для звонка
        Redis::setex("allow_signal:{$user->id}:{$contactId}", 300, 1);
        Redis::setex("allow_signal:{$contactId}:{$user->id}", 300, 1);

        broadcast(new WebRTCSignalEvent($contactId, [
            'type' => 'incoming-direct-call', 
            'callerId' => $user->id, 
            'callerName' => $user->name, 
            'from' => $user->id
        ]));
        
        return response()->json(['status' => 'calling']);
    }
}