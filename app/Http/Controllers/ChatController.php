<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Events\{MatchFoundEvent, MessageSentEvent, WebRTCSignalEvent};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, DB};
use Illuminate\Database\Query\Builder;

class ChatController extends Controller
{
    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        Matchmaking::where('user_id', $userId)->delete();

        $waitingUser = Matchmaking::where('user_id', '!=', $userId)
            ->where('status', 'waiting')
            ->first();

        if ($waitingUser instanceof Matchmaking) {
            $partnerId = $waitingUser->user_id;
            $waitingUser->update(['status' => 'matched']);
            Matchmaking::create(['user_id' => $userId, 'status' => 'matched']);

            broadcast(new MatchFoundEvent(targetUserId: $partnerId, partnerId: $userId));
            broadcast(new MatchFoundEvent(targetUserId: $userId, partnerId: $partnerId));

            return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
        }

        Matchmaking::updateOrCreate(['user_id' => $userId], ['status' => 'waiting']);
        return response()->json(['status' => 'searching']);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $partnerId = $request->input('partnerId');

        if ($partnerId) {
            broadcast(new WebRTCSignalEvent($partnerId, [
                'type' => 'peer-disconnected',
                'oldPartnerId' => $userId
            ]))->toOthers();
        }

        Matchmaking::where('user_id', $userId)->delete();
        return response()->json(['status' => 'left']);
    }

    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partnerId' => 'required|integer',
            'data' => 'required|array'
        ]);

        broadcast(new WebRTCSignalEvent($validated['partnerId'], $validated['data']))->toOthers();
        return response()->json(['status' => 'signal_sent']);
    }

    public function getContacts(): JsonResponse
    {
        $contacts = DB::table('contacts')
            ->where('contacts.user_id', Auth::id())
            ->join('users', 'users.id', '=', 'contacts.contact_id')
            ->select('users.id', 'users.name', 'users.last_seen') // Убедитесь, что last_seen тут есть
            ->get();

        return response()->json(['contacts' => $contacts]);
    }

    public function checkContact(Request $request): JsonResponse
    {
        $isContact = DB::table('contacts')
            ->where('user_id', Auth::id())
            ->where('contact_id', $request->contactId)
            ->exists();
        return response()->json(['isContact' => $isContact]);
    }

    public function toggleContact(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $contactId = $request->contactId;
        $exists = DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->exists();

        if ($exists) {
            DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->delete();
            return response()->json(['action' => 'removed']);
        }

        DB::table('contacts')->insert(['user_id' => $userId, 'contact_id' => $contactId, 'created_at' => now(), 'updated_at' => now()]);
        return response()->json(['action' => 'added']);
    }

    public function getChatHistory(int $contactId): JsonResponse
    {
        $userId = Auth::id();
        $messages = DB::table('messages')
            ->where(fn($q) => $q->where('sender_id', $userId)->where('receiver_id', $contactId))
            ->orWhere(fn($q) => $q->where('sender_id', $contactId)->where('receiver_id', $userId))
            ->orderBy('created_at', 'asc')
            ->get();

        DB::table('messages')->where('sender_id', $contactId)->where('receiver_id', $userId)->update(['is_read' => true]);
        return response()->json(['messages' => $messages]);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate(['receiver_id' => 'required|integer', 'message' => 'required|string']);
        $userId = Auth::id();

        $messageData = [
            'sender_id' => $userId,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'is_read' => false,
            'created_at' => now()->toIso8601String(),
            'updated_at' => now()->toIso8601String()
        ];

        $messageData['id'] = DB::table('messages')->insertGetId($messageData);
        broadcast(new MessageSentEvent($messageData))->toOthers();

        return response()->json(['status' => 'sent', 'message' => $messageData]);
    }

    public function callContact(Request $request): JsonResponse
    {
        broadcast(new WebRTCSignalEvent($request->contactId, [
            'type' => 'incoming-direct-call',
            'callerId' => Auth::id(),
            'callerName' => Auth::user()->name
        ]))->toOthers();

        return response()->json(['status' => 'calling']);
    }
}