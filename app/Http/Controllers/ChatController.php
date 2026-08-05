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
use Hashids\Hashids;


class ChatController extends Controller
{
    public function __construct(
        protected LeaveChat $leaveChatAction,
        protected FindPartner $findPartnerAction
    ) {}

public function index(Request $request)
    {
        if (!$request->has('accept_call') && !$request->has('call_to')) {
            $this->leaveChatAction->execute(Auth::id());
        }

        $initData = [
            'myId' => Auth::id(),
            'myInterests' => Auth::user()->interests ?? [],
            'iceServers' => config('webrtc.ice_servers'), // <--- Подтягиваем из конфига
            'translations' => __('chatroulette'),
            'currentLevel' => Auth::user()->level ?? 1,
            'totalXp' => Auth::user()->xp ?? 0,
            'targetCountry' => Auth::user()->target_country ?? 'global',
            'targetGender' => Auth::user()->target_gender ?? 'all',
            'targetAgeMin' => Auth::user()->target_age_min ?? 18,
            'targetAgeMax' => Auth::user()->target_age_max ?? 99,
        ];

        return view('roulette.index', compact('initData'));
    }

        // Вспомогательная функция декодирования
    private function decodeId(string $hashid): int
    {
        $hashids = new Hashids(config('app.key'), 10);
        $decoded = $hashids->decode($hashid);
        return empty($decoded) ? 0 : (int)$decoded[0];
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
    // 1. Валидация: теперь мы ожидаем строку (Hashid), а не число
    $request->validate(['contactId' => 'required|string']);
    
    // 2. Декодирование Hashid в реальный ID
    $receiverId = $this->decodeId($request->contactId);
    $senderId = (int)Auth::id();

    // Проверка на корректность декодирования и звонок самому себе
    if ($receiverId === 0 || $senderId === $receiverId) {
        return response()->json(['error' => 'Invalid connection protocol or self-call'], 400);
    }

    // 3. ПРОВЕРКА ДРУЖБЫ: Звонить через мессенджер можно только друзьям
    $isFriend = DB::table('contacts')
        ->where(function($q) use ($senderId, $receiverId) {
            $q->where('user_id', $senderId)->where('contact_id', $receiverId);
        })
        ->orWhere(function($q) use ($senderId, $receiverId) {
            $q->where('user_id', $receiverId)->where('contact_id', $senderId);
        })
        ->where('status', 'accepted')
        ->exists();

    if (!$isFriend) {
        return response()->json(['error' => 'Call restricted. Users are not linked.'], 403);
    }

    // 4. Проверка блокировок (Черный список)
    $isBlocked = DB::table('blocks')
        ->where(function($q) use ($senderId, $receiverId) {
            $q->where('blocker_id', $receiverId)->where('blocked_id', $senderId);
        })
        ->orWhere(function($q) use ($senderId, $receiverId) {
            $q->where('blocker_id', $senderId)->where('blocked_id', $receiverId);
        })
        ->exists();

    if ($isBlocked) {
        return response()->json(['error' => 'Connection refused by security policy'], 403);
    }

    // 5. ПРОВЕРКА: Занят ли собеседник
    $isBusy = Matchmaking::where('user_id', $receiverId)
        ->where('status', MatchmakingStatus::Matched)
        ->where('updated_at', '>=', now()->subSeconds(35)) 
        ->exists();

    if ($isBusy) {
        // Опционально: создаем запись о пропущенном вызове в сообщениях (используем реальные ID)
        Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => '📞 Missed call (Receiver was busy)',
            'is_read' => false
        ]);
        
        return response()->json(['status' => 'busy', 'message' => 'User is busy']);
    }

    // 6. ПОДГОТОВКА: Выходим из текущих очередей рулетки
    $this->leaveChatAction->execute($senderId);

    // 7. СИСТЕМНЫЙ ДОСТУП (Redis): Используем реальные Integer ID
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$senderId}:{$receiverId}", 3600, "1");
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$receiverId}:{$senderId}", 3600, "1");

    // 8. СОСТОЯНИЕ В БД: Используем реальные Integer ID
    Matchmaking::updateOrCreate(
        ['user_id' => $senderId],
        [
            'status' => MatchmakingStatus::Matched, 
            'partner_id' => $receiverId, 
            'updated_at' => now()
        ]
    );

    // 9. ОТПРАВКА СОБЫТИЯ: Маскируем ID отправителя (передаем Hashid)
    broadcast(new WebRTCSignalEvent($receiverId, [
        'type' => 'incoming-call',
        'fromName' => Auth::user()->name,
        'fromId' => Auth::user()->hashid // ВАЖНО: Передаем Hashid, чтобы фронт опознал друга
    ]));

    return response()->json(['status' => 'calling']);
}

    public function getUserInfo(string $hashid): JsonResponse
    {
        $realId = $this->decodeId($hashid);
        if ($realId === 0) abort(404);
        $user = User::findOrFail($realId);
        return response()->json([
            'id' => $user->hashid,
            'name' => $user->name,
            'level' => $user->level,
            'gender' => $user->gender, // ДОБАВЛЕНО
            'age' => $user->age,       // ДОБАВЛЕНО
            'country_code' => $user->country_code, // ДОБАВЛЕНО для флага
            'country_flag' => \App\Enums\UserCountry::getFlag($user->country_code), // ДОБАВЛЕНО
            'badge' => $user->prestige_badge, 
            'rank_name' => $user->rank_name,
            'is_online' => $user->isOnline(),
            'last_seen_human' => $user->getLastSeenForHumans(),
            'karma' => $user->karma,
            'blocked_count' => DB::table('blocks')->where('blocked_id', $user->id)->count(),
            'ban_count' => $user->ban_count,
            'vpn' => (bool)$user->is_vpn,
        ]);
    }

public function sendSignal(Request $request): JsonResponse
{
    $validated = $request->validate([
        'partnerId' => 'required|string', 
        'data' => 'required|array'
    ]);
    
    $senderId = (int)Auth::id();
    $receiverRealId = $this->decodeId($request->partnerId);
    $data = $validated['data'];

    // 1. Быстрая проверка в Redis
    $isAllowed = Redis::exists("allow_signal:{$senderId}:{$receiverRealId}");
    
    if (!$isAllowed) {
        // 2. ФОЛБЭК: Проверяем в БД в ОБЕ стороны!
        // Это важно, так как один юзер мог инициировать звонок, а другой — отправить первый сигнал
        $isAllowed = Matchmaking::where(function($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $senderId)->where('partner_id', $receiverRealId);
            })
            ->orWhere(function($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $receiverRealId)->where('partner_id', $senderId);
            })
            ->exists();
        
        // 3. Если в БД нашли — восстанавливаем ключ в Redis на лету
        if ($isAllowed) {
            Redis::setex("allow_signal:{$senderId}:{$receiverRealId}", 3600, "1");
            Redis::setex("allow_signal:{$receiverRealId}:{$senderId}", 3600, "1");
        }
    }

    // 4. Проверка для комнат (Spaces)
    if (!$isAllowed && isset($data['roomUuid'])) {
        $isAllowed = Room::where('uuid', $data['roomUuid'])->exists();
    }

    // Если всё равно нет — тогда 403
    if (!$isAllowed) {
        \Log::warning("Signal Blocked: Sender {$senderId} to Receiver {$receiverRealId}. Logic check failed.");
        return response()->json(['error' => 'Forbidden'], 403);
    }

    // Подменяем ID на хеш для фронтенда
    $data['from'] = Auth::user()->hashid; 
    
    broadcast(new WebRTCSignalEvent($receiverRealId, $data));

    return response()->json(['status' => 'signal_sent']);
}


public function sendMessage(Request $request): JsonResponse 
{
    // 1. Декодируем входящий Hashid в реальный Integer ID
    $realReceiverId = $this->decodeId($request->receiver_id);

    // 2. Валидация входных данных
    // Проверяем только сообщение, так как receiver_id мы валидируем вручную ниже
    $request->validate([
        'message' => 'required|string|max:5000'
    ]);

    // Если ID не удалось декодировать (пришел мусор вместо хеша)
    if ($realReceiverId === 0) {
        return response()->json(['error' => 'Invalid destination protocol.'], 422);
    }

    $senderId = auth()->id();

    // 3. Слой безопасности: Проверка разрешений на переписку
    
    // А. Проверяем, являются ли пользователи принятыми друзьями
    // Проверка в обе стороны, так как запись может быть создана любым из них
    $isFriend = DB::table('contacts')
        ->where(function($q) use ($senderId, $realReceiverId) {
            $q->where('user_id', $senderId)->where('contact_id', $realReceiverId);
        })
        ->orWhere(function($q) use ($senderId, $realReceiverId) {
            $q->where('user_id', $realReceiverId)->where('contact_id', $senderId);
        })
        ->where('status', 'accepted')
        ->exists();

    // Б. Проверяем, находятся ли они в активном матче рулетки прямо сейчас
    $isInMatch = \App\Models\Matchmaking::where(function($q) use ($senderId, $realReceiverId) {
            $q->where('user_id', $senderId)->where('partner_id', $realReceiverId);
        })
        ->orWhere(function($q) use ($senderId, $realReceiverId) {
            $q->where('user_id', $realReceiverId)->where('partner_id', $senderId);
        })
        ->exists();

    // Если нет ни дружбы, ни матча — это попытка взлома через API
    if (!$isFriend && !$isInMatch) {
        \Log::warning("Unauthorized bridge attempt: User {$senderId} tried to message {$realReceiverId}");
        return response()->json(['error' => 'Protocol violation. Connection not established.'], 403);
    }

    // 4. Сохранение в БД (используем только реальные integer ID)
    $message = Message::create([
        'sender_id' => $senderId,
        'receiver_id' => $realReceiverId, 
        'message' => $request->message
    ]);

    // 5. Трансляция события через сокеты (Маскируем ID обратно в Hashids)
    // Это важно: получатель в JS будет искать сообщение по хешированному ID
    $broadcastData = [
        'id' => $message->id,
        'sender_id' => auth()->user()->hashid, // Отправляем наш хеш
        'receiver_id' => $request->receiver_id, // Возвращаем тот же хеш, что прислал фронт
        'message' => $message->message,
        'created_at' => $message->created_at->toIso8601String(),
    ];

    broadcast(new MessageSentEvent($broadcastData));

    // 6. Ответ инициатору
    return response()->json([
        'status' => 'sent', 
        'message' => [
            'id' => $message->id,
            'sender_id' => auth()->user()->hashid,
            'receiver_id' => $request->receiver_id,
            'message' => $message->message,
            'created_at' => $message->created_at
        ]
    ]);
}

public function getChatHistory(string $hashid): JsonResponse
{
    $contactId = $this->decodeId($hashid);
    $userId = Auth::id();
    
    $messages = Message::where(function($q) use ($userId, $contactId) {
            $q->where('sender_id', $userId)->where('receiver_id', $contactId);
        })->orWhere(function($q) use ($userId, $contactId) {
            $q->where('sender_id', $contactId)->where('receiver_id', $userId);
        })
        ->orderBy('id', 'desc')
        ->take(100)
        ->get()
        ->reverse()
        ->values()
        ->map(function($m) {
            // Подменяем ID на Hashid для фронтенда
            return [
                'id' => $m->id,
                'sender_id' => User::find($m->sender_id)->hashid,
                'receiver_id' => User::find($m->receiver_id)->hashid,
                'message' => $m->message,
                'created_at' => $m->created_at
            ];
        });

    return response()->json(['messages' => $messages]);
}

    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

public function addContact(Request $request): JsonResponse 
{
    $request->validate(['contactId' => 'required|string']); // Валидация строки
    $contactId = $this->decodeId($request->contactId); // Декодируем в число
    $userId = Auth::id();

    if ($contactId === 0 || $userId === $contactId) return response()->json(['error' => 'Invalid ID'], 400);

    // Проверка на блок
    $isBlocked = DB::table('blocks')->where('blocker_id', $contactId)->where('blocked_id', $userId)->exists();
    if ($isBlocked) return response()->json(['error' => 'Action restricted'], 403);

    $existing = DB::table('contacts')
        ->where('user_id', $userId)
        ->where('contact_id', $contactId)
        ->first();

    if (!$existing) {
        // Создаем заявку (статус pending)
        DB::table('contacts')->insert([
            'user_id' => $userId, 
            'contact_id' => $contactId, 
            'status' => 'pending', 
            'created_at' => now(), 
            'updated_at' => now()
        ]);

        // Отправляем системное сообщение-уведомление в сокет
        $msg = Message::create([
            'sender_id' => $userId,
            'receiver_id' => $contactId,
            'message' => 'SYSTEM_FRIEND_REQUEST', // Специальный маркер для фронтенда
        ]);
        broadcast(new MessageSentEvent($msg->toArray()));

        return response()->json(['action' => 'requested', 'status' => 'pending']);
    }

    return response()->json(['action' => 'exists', 'status' => $existing->status]);
}

public function getContacts(): JsonResponse 
{ 
    $userId = Auth::id();
    $contactRows = DB::table('contacts')->where('user_id', $userId)->orWhere('contact_id', $userId)->get();
    $targetIds = $contactRows->map(fn($row) => $row->user_id == $userId ? $row->contact_id : $row->user_id)->unique();
    
    // 2. Получаем данные пользователей, исключая заблокированных
    $contacts = User::whereIn('id', $targetIds)
        ->whereNotIn('id', function($q) use ($userId) {
            $q->select('blocked_id')->from('blocks')->where('blocker_id', $userId);
        })
        ->get()
        ->map(function($u) use ($userId, $contactRows) {
            // Ищем строку отношений для этого конкретного юзера
            // Важно: берем статус именно из той строки, где МЫ — участники
            $row = DB::table('contacts')
                ->where(fn($q) => $q->where('user_id', $userId)->where('contact_id', $u->id))
                ->orWhere(fn($q) => $q->where('user_id', $u->id)->where('contact_id', $userId))
                ->first();

            return [
                'id' => $u->hashid, // МЕНЯЕМ НА HASHID
                'name' => $u->name, 
                'is_online' => $u->isOnline(), 
                'last_seen_human' => $u->getLastSeenForHumans(),
                'level' => $u->level,
                'rank_name' => $u->rank_name,
                'status' => $row ? $row->status : 'none'
            ];
        })
        ->sortByDesc('is_online')
        ->values();

    return response()->json(['contacts' => $contacts]);
}


public function sendTypingSignal(Request $request): JsonResponse 
{
    $validated = $request->validate([
        'receiver_id' => 'required|string' // Меняем на string
    ]);

    $receiverId = $this->decodeId($validated['receiver_id']);
    $senderId = (int) Auth::id();

    broadcast(new \App\Events\UserTypingEvent($receiverId, $senderId));

    return response()->json(['status' => 'ok']);
}

public function getInteractionHistory(): JsonResponse 
{
    $userId = Auth::id();

    $history = DB::table('interactions')
        ->where('interactions.user_id', $userId)
        // 1. Исключаем тех, кого мы заблокировали
        ->whereNotIn('interactions.partner_id', function($q) use ($userId) {
            $q->select('blocked_id')->from('blocks')->where('blocker_id', $userId);
        })
        // 2. Исключаем тех, кто УЖЕ является принятым другом (accepted)
        ->whereNotIn('interactions.partner_id', function($q) use ($userId) {
            $q->select('contact_id')->from('contacts')
              ->where('user_id', $userId)
              ->where('status', 'accepted');
        })
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

            // Проверяем статус в контактах (может быть null или pending)
            $contactEntry = DB::table('contacts')
                ->where('user_id', $userId)
                ->where('contact_id', $record->id)
                ->first();

            return [
                'id' => $u->hashid, // МЕНЯЕМ НА HASHID
                'name' => $record->name,
                'is_online' => $u ? $u->isOnline() : false,
                'last_seen_human' => $u ? $u->getLastSeenForHumans() : 'Давно',
                'last_met_diff' => \Carbon\Carbon::parse($record->last_at)->diffForHumans(),
                'is_blocked' => $isBlocked,
                // Флаг отправленного запроса (true если в базе есть запись, но мы знаем что она не accepted)
                'is_pending' => $contactEntry && $contactEntry->status === 'pending',
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
    $request->validate(['userId' => 'required|string']); // Ожидаем строку
    $blockerId = Auth::id();
    $blockedId = $this->decodeId($request->userId); // Декодируем

    if ($blockerId === $blockedId) return response()->json(['error' => 'Self-block'], 400);

    DB::table('blocks')->updateOrInsert([
        'blocker_id' => $blockerId,
        'blocked_id' => $blockedId
    ], ['created_at' => now(), 'updated_at' => now()]);

    return response()->json(['status' => 'blocked']);
}

public function leaderboard(): \Illuminate\View\View
{
    // Кэшируем результат на 10 минут
    $topUsers = \Illuminate\Support\Facades\Cache::remember('leaderboard_top_50', 600, function () {
        return User::whereNull('banned_until')
            ->orWhere('banned_until', '<', now())
            ->orderBy('xp', 'desc')
            ->take(50)
            ->get();
    });

    return view('leaderboard', compact('topUsers'));
}

public function getRandomIcebreakerIndex(): \Illuminate\Http\JsonResponse
{
    $path = storage_path('app/icebreakers.json');
    if (!file_exists($path)) return response()->json(['index' => 0]);

    $data = json_decode(file_get_contents($path), true);
    // Берем количество вопросов из английской секции (они должны быть синхронизированы)
    $count = count($data['en']);
    
    return response()->json([
        'index' => rand(0, $count - 1)
    ]);
}

public function getIcebreakerContent(int $index): \Illuminate\Http\JsonResponse
{
    $path = storage_path('app/icebreakers.json');
    $data = json_decode(file_get_contents($path), true);
    $locale = app()->getLocale();

    // Получаем список вопросов для языка пользователя, если нет - берем 'en'
    $questions = $data[$locale] ?? $data['en'];
    
    // Если индекс вне диапазона (вдруг добавили/удалили вопросы), берем первый
    $question = $questions[$index] ?? $questions[0];

    return response()->json(['question' => $question]);
}

public function acceptFriend(Request $request): JsonResponse
{
    $senderId = (int)$request->senderId; // Тот, кто прислал запрос
    $myId = Auth::id();

    DB::transaction(function() use ($senderId, $myId) {
        // 1. Обновляем статус входящего запроса у себя (или создаем если не было)
        DB::table('contacts')->updateOrInsert(
            ['user_id' => $myId, 'contact_id' => $senderId],
            ['status' => 'accepted', 'updated_at' => now()]
        );
        
        // 2. Обновляем статус запроса у того, кто просил дружбу
        DB::table('contacts')
            ->where('user_id', $senderId)
            ->where('contact_id', $myId)
            ->update(['status' => 'accepted', 'updated_at' => now()]);

        // 3. Отправляем уведомление об успехе
        $msg = \App\Models\Message::create([
            'sender_id' => $myId,
            'receiver_id' => $senderId,
            'message' => 'SYSTEM_FRIEND_ACCEPTED',
        ]);
        broadcast(new \App\Events\MessageSentEvent($msg->toArray()));
    });

    return response()->json(['status' => 'success']);
}

public function declineFriend(Request $request): JsonResponse
{
    $senderId = (int)$request->senderId;
    $myId = Auth::id();

    // Просто удаляем заявку из таблицы contacts
    DB::table('contacts')
        ->where('user_id', $senderId)
        ->where('contact_id', $myId)
        ->delete();

    return response()->json(['status' => 'declined']);
}

public function removeContact(Request $request): JsonResponse 
{
    $contactId = (int)$request->contactId;
    $userId = Auth::id();

    // Удаляем связь в обе стороны, так как дружба была взаимной
    DB::table('contacts')
        ->where(function($q) use ($userId, $contactId) {
            $q->where('user_id', $userId)->where('contact_id', $contactId);
        })
        ->orWhere(function($q) use ($userId, $contactId) {
            $q->where('user_id', $contactId)->where('contact_id', $userId);
        })
        ->delete();

    return response()->json([
        'success' => true,
        'action' => 'removed',
        'status' => 'success'
    ]);
}

public function clearChat(Request $request): JsonResponse
{
    $request->validate(['contactId' => 'required|string']);
    $contactId = $this->decodeId($request->contactId);
    $userId = Auth::id();

    Message::where(function($q) use ($userId, $contactId) {
        $q->where('sender_id', $userId)->where('receiver_id', $contactId);
    })->orWhere(function($q) use ($userId, $contactId) {
        $q->where('sender_id', $contactId)->where('receiver_id', $userId);
    })->delete();

    return response()->json(['status' => 'success']);
}

}