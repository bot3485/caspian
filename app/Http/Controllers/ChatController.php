<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Models\Message;
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
        Matchmaking::create(['user_id' => $userId, 'status' => MatchmakingStatus::Searching, 'updated_at' => now()]);
        $partnerId = $this->findPartnerAction->execute($userId);
        return response()->json(['status' => $partnerId ? 'matched' : 'searching', 'partnerId' => $partnerId]);
    }

    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate(['partnerId' => 'required|integer', 'data' => 'required|array']);
        $senderId = Auth::id();
        $receiverId = (int)$validated['partnerId'];
        $data = $validated['data'];
        $data['from'] = $senderId;

        broadcast(new WebRTCSignalEvent($receiverId, $data));
        return response()->json(['status' => 'signal_sent']);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

    // ТУТ ИСПРАВЛЕННЫЙ TOGGLE КОНТАКТОВ
    public function addContact(Request $request): JsonResponse 
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $contactId = (int)$request->contactId;
        $userId = Auth::id();

        if ($userId === $contactId) return response()->json(['error' => 'Self-addition'], 400);

        $exists = DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->exists();

        if ($exists) {
            DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->delete();
            return response()->json(['action' => 'removed', 'isFriend' => false]);
        }

        DB::table('contacts')->insert(['user_id' => $userId, 'contact_id' => $contactId, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['action' => 'added', 'isFriend' => true]);
    }

    public function getContacts(): JsonResponse 
    { 
        $contacts = \App\Models\User::whereIn('id', function($query) {
                $query->select('contact_id')
                    ->from('contacts')
                    ->where('user_id', Auth::id());
            })
            ->select('id', 'name', 'last_seen')
            ->get()
            ->map(function($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_online' => $user->isOnline(),
                    'last_seen_human' => $user->getLastSeenForHumans(),
                ];
            });
            
        return response()->json(['contacts' => $contacts]);
    }

    public function callContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer']);
        $receiverId = (int)$request->contactId;
        Redis::setex("allow_signal:".Auth::id().":{$receiverId}", 300, 1);
        
        broadcast(new WebRTCSignalEvent($receiverId, [
            'type' => 'incoming-call',
            'fromName' => Auth::user()->name,
            'fromId' => Auth::id()
        ]));
        return response()->json(['status' => 'calling']);
    }

    public function getChatHistory(int $contactId): JsonResponse
    {
        $userId = Auth::id();
        $messages = Message::where(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })->orderBy('created_at', 'asc')->take(50)->get();
        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate(['receiver_id' => 'required|integer', 'message' => 'required|string']);
        $message = Message::create(['sender_id' => Auth::id(), 'receiver_id' => $validated['receiver_id'], 'message' => $validated['message']]);
        broadcast(new MessageSentEvent($message->toArray()))->toOthers();
        return response()->json(['status' => 'sent', 'message' => $message]);
    }

    public function sendTypingSignal(Request $request): JsonResponse 
    {
        broadcast(new \App\Events\UserTypingEvent($request->receiver_id, Auth::id()))->toOthers();
        return response()->json(['status' => 'sent']);
    }
}