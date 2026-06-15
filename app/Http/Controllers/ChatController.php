<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Events\MatchFoundEvent;
use App\Events\MessageSentEvent;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ChatController extends Controller
{
    public function startSearching(Request $request)
    {
        $userId = Auth::id();
        Matchmaking::where('user_id', $userId)->delete();

        $waitingUser = Matchmaking::where('user_id', '!=', $userId)
            ->where('status', 'waiting')
            ->first();

        if ($waitingUser) {
            $partnerId = $waitingUser->user_id;
            $waitingUser->update(['status' => 'matched']);
            Matchmaking::create(['user_id' => $userId, 'status' => 'matched']);

            broadcast(new MatchFoundEvent($partnerId, $userId));
            broadcast(new MatchFoundEvent($userId, $partnerId));

            return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
        }

        Matchmaking::updateOrCreate(['user_id' => $userId], ['status' => 'waiting']);
        return response()->json(['status' => 'searching']);
    }

    public function leaveChat(Request $request)
    {
        $userId = Auth::id();
        $partnerId = $request->input('partnerId');

        if ($partnerId) {
            broadcast(new \App\Events\WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'oldPartnerId' => $userId
            ]))->toOthers();
        }

        Matchmaking::where('user_id', $userId)->delete();
        return response()->json(['status' => 'left']);
    }

    public function sendSignal(Request $request)
    {
        $request->validate([
            'partnerId' => 'required|integer',
            'data' => 'required|array'
        ]);
        broadcast(new \App\Events\WebRTCSignalEvent($request->partnerId, $request->data))->toOthers();
        return response()->json(['status' => 'signal_sent']);
    }

    // --- ЛОГИКА КОНТАКТОВ И МЕССЕНДЖЕРА ---

    // Получить список друзей
    public function getContacts()
    {
        $userId = Auth::id();
        
        // Достаем пользователей, которые сохранены в таблице contacts
        $contacts = DB::table('contacts')
            ->where('contacts.user_id', $userId)
            ->join('users', 'users.id', '=', 'contacts.contact_id')
            ->select('users.id', 'users.name', 'users.updated_at as last_seen')
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    // Проверка статуса контакта
    public function checkContact(Request $request)
    {
        $request->validate(['contactId' => 'required|integer']);
        $isContact = DB::table('contacts')->where('user_id', Auth::id())->where('contact_id', $request->contactId)->exists();
        return response()->json(['isContact' => $isContact]);
    }

    // Добавить/Удалить из контактов
    public function toggleContact(Request $request)
    {
        $request->validate(['contactId' => 'required|integer']);
        $userId = Auth::id();
        $contactId = $request->contactId;

        $exists = DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->exists();

        if ($exists) {
            DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->delete();
            return response()->json(['action' => 'removed']);
        } else {
            DB::table('contacts')->insert([
                'user_id' => $userId,
                'contact_id' => $contactId,
                'created_at' => now(),
                'updated_at' => now()
            ]);
            return response()->json(['action' => 'added']);
        }
    }

    // Получить историю сообщений с конкретным контактом
    public function getChatHistory($contactId)
    {
        $userId = Auth::id();

        $messages = DB::table('messages')
            ->where(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        // Помечаем входящие как прочитанные
        DB::table('messages')->where('sender_id', $contactId)->where('receiver_id', $userId)->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }

    // Сохранить и отправить текстовое сообщение другу
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer',
            'message' => 'required|string'
        ]);

        $userId = Auth::id();

        $messageId = DB::table('messages')->insertGetId([
            'sender_id' => $userId,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $messageData = [
            'id' => $messageId,
            'sender_id' => $userId,
            'receiver_id' => $request->receiver_id,
            'message' => $request->message,
            'created_at' => now()->toIso8601String()
        ];

        // Вещаем событие в сокет получателя
        broadcast(new MessageSentEvent($messageData))->toOthers();

        return response()->json(['status' => 'sent', 'message' => $messageData]);
    }

    // Прямой вызов другу из списка контактов
    public function callContact(Request $request)
    {
        $request->validate(['contactId' => 'required|integer']);
        
        // Отправляем системное сокет-уведомление другу о том, что мы ему звоним прямо сейчас
        broadcast(new \App\Events\WebRTCSignalEvent($request->contactId, [
            'type' => 'incoming-direct-call',
            'callerId' => Auth::id(),
            'callerName' => Auth::user()->name
        ]))->toOthers();

        return response()->json(['status' => 'calling']);
    }
    
    public function sendTypingSignal(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|integer',
            'is_typing' => 'required|boolean'
        ]);

        broadcast(new \App\Events\WebRTCSignalEvent($request->receiver_id, [
            'type' => 'friend-typing',
            'sender_id' => Auth::id(),
            'is_typing' => $request->is_typing
        ]))->toOthers();

        return response()->json(['status' => 'typing_signaled']);
    }
}