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

    public function index()
    {
        $userId = Auth::id();
        // При заходе на страницу ГАРАНТИРОВАННО удаляем пользователя из всех очередей
        (new \App\Actions\LeaveChat())->execute($userId);
        
        return view('chat');
    }

    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // 1. Сначала полностью чистим всё, что могло остаться
        $this->leaveChatAction->execute($userId);
        
        // 2. Создаем запись в БД со статусом SEARCHING и свежим временем
        \App\Models\Matchmaking::create([
            'user_id' => $userId, 
            'status' => \App\Enums\MatchmakingStatus::Searching, 
            'updated_at' => now()
        ]);
        
        // 3. Только ТЕПЕРЬ запускаем поиск партнера
        $partnerId = $this->findPartnerAction->execute($userId);
        
        return response()->json([
            'status' => $partnerId ? 'matched' : 'searching', 
            'partnerId' => $partnerId
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

        // Продлеваем сессию в Redis (это дешево)
        Redis::setex("user_active:{$senderId}", 30, "1");

        $matchKey = "allow_signal:{$senderId}:{$receiverId}";
        
        // Проверка прав через Redis (быстро)
        $isAllowed = Redis::exists($matchKey);
        
        if (!$isAllowed && isset($data['roomUuid'])) {
            // Если это комната, проверяем кэш, а не БД
            $roomKey = "room_exists:{$data['roomUuid']}";
            $isAllowed = Redis::get($roomKey) ?? \App\Models\Room::where('uuid', $data['roomUuid'])->exists();
            
            // Кэшируем результат на 10 минут, чтобы не дергать БД
            if ($isAllowed && !Redis::exists($roomKey)) {
                Redis::setex($roomKey, 600, "1");
            }
        }

        if (!$isAllowed) {
            return response()->json(['error' => 'Unauthorized signaling'], 403);
        }

        $data['from'] = $senderId;
        broadcast(new \App\Events\WebRTCSignalEvent($receiverId, $data));

        return response()->json(['status' => 'signal_sent']);
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

        DB::table('contacts')->insert([
            'user_id' => $userId, 
            'contact_id' => $contactId, 
            'created_at' => now(), 
            'updated_at' => now()
        ]);
        return response()->json(['action' => 'added', 'isFriend' => true]);
    }

    public function getContacts(): JsonResponse 
    { 
        $userId = Auth::id();
        $contacts = \App\Models\User::whereIn('id', function($query) use ($userId) {
                $query->select('contact_id')->from('contacts')->where('user_id', $userId);
            })
            ->whereNotExists(function($query) use ($userId) {
                $query->select(DB::raw(1))->from('blocks')
                    ->where('blocker_id', $userId)
                    ->whereColumn('blocked_id', 'users.id');
            })
            ->select('id', 'name', 'last_seen')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->name,
                'is_online' => $user->isOnline(),
                'last_seen_human' => $user->getLastSeenForHumans(),
            ]);
            
        return response()->json(['contacts' => $contacts]);
    }

    public function callContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer']);
        $receiverId = (int)$request->contactId;
        $senderId = Auth::id();

        // 1. Создаем временный "матч" в базе, чтобы сигналы проходили проверку безопасности
        \App\Models\Matchmaking::updateOrCreate(
            ['user_id' => $senderId],
            ['status' => \App\Enums\MatchmakingStatus::Matched, 'partner_id' => $receiverId, 'updated_at' => now()]
        );
        \App\Models\Matchmaking::updateOrCreate(
            ['user_id' => $receiverId],
            ['status' => \App\Enums\MatchmakingStatus::Matched, 'partner_id' => $senderId, 'updated_at' => now()]
        );

        // 2. Даем права в Redis
        Redis::setex("allow_signal:{$senderId}:{$receiverId}", 300, 1);
        Redis::setex("allow_signal:{$receiverId}:{$senderId}", 300, 1);
        
        broadcast(new \App\Events\WebRTCSignalEvent($receiverId, [
            'type' => 'incoming-call',
            'fromName' => Auth::user()->name,
            'fromId' => $senderId
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
        $validated = $request->validate([
            'receiver_id' => 'required|integer', 
            'message' => 'required|string'
        ]);

        $message = \App\Models\Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message']
        ]);
        
        // Вещаем событие получателю
        broadcast(new \App\Events\MessageSentEvent($message->toArray()));

        return response()->json(['status' => 'sent', 'message' => $message]);
    }

    public function sendTypingSignal(Request $request): JsonResponse 
    {
        $request->validate(['receiver_id' => 'required|integer']);
        broadcast(new \App\Events\UserTypingEvent($request->receiver_id, Auth::id()))->toOthers();
        return response()->json(['status' => 'ok']);
    }

    public function getInteractionHistory(): JsonResponse 
    {
        $userId = Auth::id();
        
        $history = DB::table('interactions')
            ->join('users', 'interactions.partner_id', '=', 'users.id')
            ->where('interactions.user_id', $userId)
            ->whereNotExists(function($query) use ($userId) {
                $query->select(DB::raw(1))->from('blocks')
                    ->where('blocker_id', $userId)
                    ->whereColumn('blocked_id', 'interactions.partner_id');
            })
            ->select('users.id', 'users.name', 'users.last_seen', 'interactions.last_at')
            ->orderByDesc('interactions.last_at')
            ->get()
            ->unique('id') // Убираем дубликаты
            ->map(function($user) {
                $u = \App\Models\User::find($user->id);
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'is_online' => $u->isOnline(),
                    'last_seen_human' => $u->getLastSeenForHumans(),
                    'last_met_diff' => \Carbon\Carbon::parse($user->last_at)->diffForHumans(),
                    'last_at' => $user->last_at
                ];
            })
            ->values()
            ->toArray();

        return response()->json(['history' => $history]);
    }

    public function getBlockedUsers(): JsonResponse
    {
        $userId = Auth::id();
        $blocked = DB::table('blocks')
            ->join('users', 'blocks.blocked_id', '=', 'users.id')
            ->where('blocks.blocker_id', $userId)
            ->select('users.id', 'users.name', 'blocks.created_at as blocked_at')
            ->orderByDesc('blocks.created_at')
            ->get();

        return response()->json(['blocked' => $blocked]);
    }

    public function unblockUser(Request $request): JsonResponse
    {
        $request->validate(['blockedId' => 'required|integer']);
        DB::table('blocks')
            ->where('blocker_id', Auth::id())
            ->where('blocked_id', $request->blockedId)
            ->delete();

        return response()->json(['status' => 'unblocked']);
    }
}