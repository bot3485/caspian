<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Models\Message;
use App\Models\User;
use App\Models\Room;
use App\Actions\FindPartner;
use App\Actions\LeaveChat;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;
use App\Events\MessageSentEvent;
use App\Events\UserTypingEvent;
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

    public function index(Request $request)
    {
        $userId = Auth::id();
        if (!$request->has('accept_call')) {
            $this->leaveChatAction->execute($userId);
        }
        return view('chat');
    }

    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $this->leaveChatAction->execute($userId);
        Matchmaking::create([
            'user_id' => $userId, 
            'status' => MatchmakingStatus::Searching, 
            'updated_at' => now()
        ]);
        $partnerId = $this->findPartnerAction->execute($userId);
        return response()->json(['status' => $partnerId ? 'matched' : 'searching', 'partnerId' => $partnerId]);
    }

    public function callContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer']);
        $receiverId = (int)$request->contactId;
        $senderId = Auth::id();

        if ($senderId === $receiverId) return response()->json(['error' => 'Self-call'], 400);

        $isBusy = Matchmaking::where('user_id', $receiverId)
            ->where('status', MatchmakingStatus::Matched)
            ->exists();

        if ($isBusy) {
            $msg = Message::create([
                'sender_id' => $senderId,
                'receiver_id' => $receiverId,
                'message' => '📞 Пропущенный вызов (Абонент занят)'
            ]);
            broadcast(new MessageSentEvent($msg->toArray()));
            return response()->json(['status' => 'busy', 'message' => 'Собеседник сейчас занят']);
        }

        $this->leaveChatAction->execute($senderId);
        
        // Регистрируем "встречу" в истории сразу при попытке звонка
        DB::table('interactions')->updateOrInsert(['user_id' => $senderId, 'partner_id' => $receiverId], ['last_at' => now()]);
        DB::table('interactions')->updateOrInsert(['user_id' => $receiverId, 'partner_id' => $senderId], ['last_at' => now()]);

        Matchmaking::updateOrCreate(
            ['user_id' => $senderId],
            ['status' => MatchmakingStatus::Matched, 'partner_id' => $receiverId, 'updated_at' => now()]
        );
        
        Redis::setex("allow_signal:{$senderId}:{$receiverId}", 300, "1");
        Redis::setex("allow_signal:{$receiverId}:{$senderId}", 300, "1");
        
        broadcast(new WebRTCSignalEvent($receiverId, [
            'type' => 'incoming-call',
            'fromName' => Auth::user()->name,
            'fromId' => $senderId
        ]));

        return response()->json(['status' => 'calling']);
    }

    public function getUserInfo(User $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'level' => $user->level,
            'rank_name' => $user->rank_name,
            'is_online' => $user->isOnline(),
            'last_seen_human' => $user->getLastSeenForHumans()
        ]);
    }

    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate(['partnerId' => 'required|integer', 'data' => 'required|array']);
        $senderId = (int)Auth::id();
        $receiverId = (int)$validated['partnerId'];
        $data = $validated['data'];

        $isAllowed = Redis::exists("allow_signal:{$senderId}:{$receiverId}");
        if (!$isAllowed && isset($data['roomUuid'])) {
            $isAllowed = Room::where('uuid', $data['roomUuid'])->exists();
        }

        if (!$isAllowed) return response()->json(['error' => 'Unauthorized'], 403);

        $data['from'] = $senderId;
        broadcast(new WebRTCSignalEvent($receiverId, $data));
        return response()->json(['status' => 'signal_sent']);
    }

    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate(['receiver_id' => 'required|integer', 'message' => 'required|string']);
        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message']
        ]);
        broadcast(new MessageSentEvent($message->toArray()));
        return response()->json(['status' => 'sent', 'message' => $message]);
    }

    public function getChatHistory(int $contactId): JsonResponse
    {
        $userId = Auth::id();
        $messages = Message::where(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })->orderBy('created_at', 'asc')->take(100)->get();
        return response()->json(['messages' => $messages]);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

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
        $userId = Auth::id();
        $contacts = User::whereIn('id', function($query) use ($userId) {
                $query->select('contact_id')->from('contacts')->where('user_id', $userId);
            })->select('id', 'name', 'last_seen')->get()
            ->map(fn($u) => ['id' => $u->id, 'name' => $u->name, 'is_online' => $u->isOnline(), 'last_seen_human' => $u->getLastSeenForHumans()]);
        return response()->json(['contacts' => $contacts]);
    }

    public function sendTypingSignal(Request $request): JsonResponse 
    {
        $request->validate(['receiver_id' => 'required|integer']);
        // Шлем событие: КТО печатает (senderId) и КОМУ (receiverId)
        broadcast(new UserTypingEvent($request->receiver_id, Auth::id()));
        return response()->json(['status' => 'ok']);
    }

    public function getInteractionHistory(): JsonResponse 
    {
        $userId = Auth::id();
        // Получаем последние 50 уникальных взаимодействий
        $history = DB::table('interactions')
            ->where('user_id', $userId)
            ->join('users', 'interactions.partner_id', '=', 'users.id')
            ->select('users.id', 'users.name', 'users.last_seen', 'interactions.last_at')
            ->orderByDesc('interactions.last_at')
            ->limit(50)
            ->get()
            ->map(function($record) {
                $u = User::find($record->id);
                return [
                    'id' => $record->id,
                    'name' => $record->name,
                    'is_online' => $u ? $u->isOnline() : false,
                    'last_seen_human' => $u ? $u->getLastSeenForHumans() : 'Давно',
                    'last_met_diff' => \Carbon\Carbon::parse($record->last_at)->diffForHumans()
                ];
            });

        return response()->json(['history' => $history]);
    }

    public function getBlockedUsers(): JsonResponse
    {
        $userId = Auth::id();
        $blocked = DB::table('blocks')->join('users', 'blocks.blocked_id', '=', 'users.id')->where('blocks.blocker_id', $userId)->select('users.id', 'users.name', 'blocks.created_at as blocked_at')->orderByDesc('blocks.created_at')->get();
        return response()->json(['blocked' => $blocked]);
    }

    public function unblockUser(Request $request): JsonResponse
    {
        $request->validate(['blockedId' => 'required|integer']);
        DB::table('blocks')->where('blocker_id', Auth::id())->where('blocked_id', $request->blockedId)->delete();
        return response()->json(['status' => 'unblocked']);
    }
}