<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Models\Message;
use App\Models\User;
use App\Models\Room; // <--- ДОБАВЬТЕ ЭТУ СТРОКУ
use App\Actions\FindPartner;
use App\Actions\LeaveChat;
use App\Enums\MatchmakingStatus;
use App\Events\WebRTCSignalEvent;
use App\Events\MessageSentEvent;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\{Auth, Redis, DB};

class ChatController extends Controller
{
    public function __construct(
        protected LeaveChat $leaveChatAction,
        protected FindPartner $findPartnerAction
    ) {}

    public function index(Request $request)
    {
        // Если зашли просто так (не по звонку), выходим из старых очередей
        if (!$request->has('accept_call') && !$request->has('call_to')) {
            $this->leaveChatAction->execute(Auth::id());
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
    $isBlocked = DB::table('blocks')
    ->where(fn($q) => $q->where('blocker_id', $receiverId)->where('blocked_id', $senderId))
    ->orWhere(fn($q) => $q->where('blocker_id', $senderId)->where('blocked_id', $receiverId))
    ->exists();

    if ($isBlocked) {
        return response()->json(['error' => 'Connection refused by security policy'], 403);
    }

    $request->validate(['contactId' => 'required|integer']);
    $receiverId = (int)$request->contactId;
    $senderId = Auth::id();

    if ($senderId === $receiverId) return response()->json(['error' => 'Self-call'], 400);

    // 1. ПРОВЕРКА: Занят ли собеседник (в рулетке или в другом приватном звонке)
    $isBusy = Matchmaking::where('user_id', $receiverId)
        ->where('status', MatchmakingStatus::Matched)
        ->exists();

    if ($isBusy) {
        // Создаем сообщение о пропущенном вызове в базу
        $msg = Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => '📞 Missed call (Receiver was busy)'
        ]);
        // Отправляем событие сообщения, чтобы оно появилось в мессенджере у получателя
        broadcast(new MessageSentEvent($msg->toArray()));
        
        return response()->json(['status' => 'busy', 'message' => 'User is busy']);
    }

    // 2. Если свободен — готовим звонок
    $this->leaveChatAction->execute($senderId);
    
    Matchmaking::updateOrCreate(
        ['user_id' => $senderId],
        ['status' => MatchmakingStatus::Matched, 'partner_id' => $receiverId, 'updated_at' => now()]
    );
    
    // Даем права на сигналы в Redis
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$senderId}:{$receiverId}", 3600, "1");
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$receiverId}:{$senderId}", 3600, "1");
    
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
        $validated = $request->validate([
            'partnerId' => 'required|integer', 
            'data' => 'required|array'
        ]);
        
        $senderId = (int)Auth::id();
        $receiverId = (int)$validated['partnerId'];
        $data = $validated['data'];

        // 1. Проверка: это приватный чат (рулетка)?
        $isAllowed = Redis::exists("allow_signal:{$senderId}:{$receiverId}");

        if (!$isAllowed) {
            // Фолбэк для рулетки
            $isAllowed = Matchmaking::where('user_id', $senderId)
                ->where('partner_id', $receiverId)
                ->exists();
            
            if ($isAllowed) {
                Redis::setex("allow_signal:{$senderId}:{$receiverId}", 3600, "1");
            }
        }

        // 2. Проверка: это групповая комната (Spaces)?
        // В объекте 'data' из JS мы передаем roomUuid
        if (!$isAllowed && isset($data['roomUuid'])) {
            // Здесь мы проверяем, существует ли комната. 
            // Для максимальной безопасности можно добавить проверку участия пользователя в Presence-канале.
            $isAllowed = Room::where('uuid', $data['roomUuid'])->exists();
        }

        if (!$isAllowed) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $data['from'] = $senderId;
        
        // Отправляем сигнал получателю
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
    
    // 1. Получаем ID всех пользователей, которых заблокировал текущий юзер
    $blockedIds = DB::table('blocks')
        ->where('blocker_id', $userId)
        ->pluck('blocked_id');

    // 2. Получаем контакты, исключая тех, кто находится в ЧС
    $contacts = User::whereIn('id', function($query) use ($userId) {
            $query->select('contact_id')
                ->from('contacts')
                ->where('user_id', $userId);
        })
        ->whereNotIn('id', $blockedIds) // Исключаем заблокированных
        ->select('id', 'name', 'last_seen', 'level') 
        ->orderBy('last_seen', 'desc')
        ->get()
        ->map(fn($u) => [
            'id' => $u->id, 
            'name' => $u->name, 
            'is_online' => $u->isOnline(), 
            'last_seen_human' => $u->getLastSeenForHumans(),
            'level' => $u->level,
            // rank_name берется из модели User (Accessor)
            'rank_name' => $u->rank_name 
        ]);

    return response()->json(['contacts' => $contacts]);
}

public function sendTypingSignal(Request $request): JsonResponse 
{
    // Валидируем, чтобы receiver_id точно был
    $validated = $request->validate([
        'receiver_id' => 'required|integer'
    ]);

    $receiverId = (int) $validated['receiver_id'];
    $senderId = (int) Auth::id();

    // Отправляем событие
    broadcast(new \App\Events\UserTypingEvent($receiverId, $senderId))->toOthers();

    return response()->json(['status' => 'ok']);
}

public function getInteractionHistory(): JsonResponse 
{
    $userId = Auth::id();

    $blockedIds = DB::table('blocks')->where('blocker_id', $userId)->pluck('blocked_id');
    $history = DB::table('interactions')
        ->where('interactions.user_id', $userId)
        ->whereNotIn('interactions.partner_id', $blockedIds) // Скрываем из истории
        ->join('users', 'interactions.partner_id', '=', 'users.id')
        ->select(
            'users.id', 
            'users.name', 
            'users.last_seen', 
            'interactions.last_at'
        )
        ->orderByDesc('interactions.last_at')
        ->limit(50)
        ->get()
        ->map(function($record) use ($userId) {
            $u = User::find($record->id);
            
            $isBlocked = DB::table('blocks')
                ->where('blocker_id', $userId)
                ->where('blocked_id', $record->id)
                ->exists();

            // ПРОВЕРКА: является ли этот пользователь уже другом
            $isFriend = DB::table('contacts')
                ->where('user_id', $userId)
                ->where('contact_id', $record->id)
                ->exists();

            return [
                'id' => $record->id,
                'name' => $record->name,
                'is_online' => $u ? $u->isOnline() : false,
                'last_seen_human' => $u ? $u->getLastSeenForHumans() : 'Давно',
                'last_met_diff' => \Carbon\Carbon::parse($record->last_at)->diffForHumans(),
                'is_blocked' => $isBlocked,
                'is_friend' => $isFriend, // Добавляем этот флаг
                'level' => $u->level ?? 1,
                'rank_name' => $u->rank_name ?? 'Newbie'
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
    
    // Удаляем запись из ЧС
    DB::table('blocks')
        ->where('blocker_id', Auth::id())
        ->where('blocked_id', $request->blockedId)
        ->delete();

    return response()->json(['status' => 'unblocked']);
}

    public function blockUser(Request $request): JsonResponse
{
    $request->validate(['userId' => 'required|integer|exists:users,id']);
    $blockerId = Auth::id();
    $blockedId = (int)$request->userId;

    if ($blockerId === $blockedId) return response()->json(['error' => 'Self-block'], 400);

    DB::table('blocks')->updateOrInsert([
        'blocker_id' => $blockerId,
        'blocked_id' => $blockedId
    ], ['created_at' => now(), 'updated_at' => now()]);

    return response()->json(['status' => 'blocked']);
}
}