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
        
        // Получаем числовой ID
        $partnerId = $this->findPartnerAction->execute($userId);
        
        // Маскируем в Hashid для фронтенда
        $partnerHashid = null;
        if ($partnerId) {
            $partnerHashid = User::find($partnerId)->hashid;
        }
        
        return response()->json([
            'status' => $partnerId ? 'matched' : 'searching', 
            'partnerId' => $partnerHashid // <--- Отправляем строку!
        ]);
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

    DB::table('interactions')->updateOrInsert(
        ['user_id' => $senderId, 'partner_id' => $receiverId], 
        ['last_at' => now()]
    );
    DB::table('interactions')->updateOrInsert(
        ['user_id' => $receiverId, 'partner_id' => $senderId], 
        ['last_at' => now()]
    );

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
    // 1. Валидация входных данных
    $validated = $request->validate([
        'partnerId' => 'required|string', // Ожидаем Hashid
        'data'      => 'required|array'
    ]);
    
    $senderId = (int) Auth::id();
    $data = $validated['data'];

    // 2. Декодирование Hashid получателя
    $receiverRealId = $this->decodeId($request->partnerId);

    // Если Hashid невалидный или равен 0 — сразу прерываем (ошибка протокола)
    if (!$receiverRealId || $receiverRealId === $senderId) {
        return response()->json(['error' => 'Invalid destination protocol'], 422);
    }

    // 3. ПРОВЕРКА РАЗРЕШЕНИЯ (Multi-layer)
    $isAllowed = false;

    // А. Быстрый чек в Redis (для рулетки и звонков друзьям)
    if (Redis::exists("allow_signal:{$senderId}:{$receiverRealId}")) {
        $isAllowed = true;
    } 
    // Б. Фолбэк: Проверка активного матча в БД (на случай перезагрузки Redis/Octane)
    else {
        $isAllowed = Matchmaking::where(function($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $senderId)->where('partner_id', $receiverRealId);
            })
            ->orWhere(function($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $receiverRealId)->where('partner_id', $senderId);
            })
            ->exists();

        // Если нашли в БД — восстанавливаем ключ в Redis для скорости следующего пакета
        if ($isAllowed) {
            Redis::setex("allow_signal:{$senderId}:{$receiverRealId}", 3600, "1");
            Redis::setex("allow_signal:{$receiverRealId}:{$senderId}", 3600, "1");
        }
    }

    // В. Проверка для групповых комнат (Spaces)
    if (!$isAllowed && isset($data['roomUuid'])) {
        $isAllowed = Room::where('uuid', $data['roomUuid'])->exists();
    }

    // Г. ДОБАВЛЯЕМ ПРОВЕРКУ ДРУЗЕЙ (КОНТАКТОВ)
if (!$isAllowed) {
        $isAllowed = \DB::table('contacts')
            ->where(function ($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $senderId)->where('contact_id', $receiverRealId);
            })
            ->orWhere(function ($q) use ($senderId, $receiverRealId) {
                $q->where('user_id', $receiverRealId)->where('contact_id', $senderId);
            })
            ->where('status', 'accepted')
            ->exists();

        // Если они подтвержденные друзья, кешируем разрешение в Redis
        if ($isAllowed) {
            Redis::setex("allow_signal:{$senderId}:{$receiverRealId}", 3600, "1");
            Redis::setex("allow_signal:{$receiverRealId}:{$senderId}", 3600, "1");
        }
    }

    // Финальный вердикт безопасности
    if (!$isAllowed) {
        \Log::warning("Blocked Signal attempt: Sender {$senderId} -> Receiver {$receiverRealId}");
        return response()->json(['error' => 'Forbidden: Connection not authorized'], 403);
    }

    // 4. ПОДГОТОВКА ДАННЫХ ДЛЯ ФРОНТЕНДА
    // Очень важно: фронт работает ТОЛЬКО с Hashids.
    // Подменяем 'from' на строковый Hashid.
    $data['from'] = (string) Auth::user()->hashid;

    // 5. ТРАНСЛЯЦИЯ
    // Отправляем событие на ЧИСЛОВОЙ приватный канал получателя
    // (Receiver слушает PrivateChannel("user.5"))
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

    // ДОБАВЛЯЕМ ФИКСАЦИЮ В ИСТОРИЮ ПРИ ОТПРАВКЕ СООБЩЕНИЯ
    DB::table('interactions')->updateOrInsert(
        ['user_id' => $senderId, 'partner_id' => $realReceiverId], 
        ['last_at' => now()]
    );
    DB::table('interactions')->updateOrInsert(
        ['user_id' => $realReceiverId, 'partner_id' => $senderId], 
        ['last_at' => now()]
    );

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
    $request->validate(['contactId' => 'required|string']);
    $contactId = $this->decodeId($request->contactId);
    $userId = Auth::id();

    if ($contactId === 0 || $userId === $contactId) return response()->json(['error' => 'Error'], 400);

    return DB::transaction(function() use ($userId, $contactId) {
        // Проверяем, нет ли уже запроса от нас к нему
        $existing = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->first();

        // Проверяем, нет ли встречного запроса (от него к нам)
        $inverse = DB::table('contacts')
            ->where('user_id', $contactId)
            ->where('contact_id', $userId)
            ->first();

        if ($existing && $existing->status === 'accepted') {
            return response()->json(['status' => 'already_friends']);
        }

        // Логика взаимного принятия:
        if ($inverse && $inverse->status === 'pending') {
            // Раз он уже просил, а мы нажали "добавить" — значит мы согласны!
            DB::table('contacts')->where('id', $inverse->id)->update(['status' => 'accepted']);
            DB::table('contacts')->updateOrInsert(
                ['user_id' => $userId, 'contact_id' => $contactId],
                ['status' => 'accepted', 'updated_at' => now()]
            );
            $this->sendSystemMessage($userId, $contactId, 'SYSTEM_FRIEND_ACCEPTED');
            return response()->json(['status' => 'accepted', 'action' => 'mutual']);
        }

        if ($existing && $existing->status === 'pending') {
            return response()->json(['status' => 'already_sent']);
        }

        // Обычный новый запрос
        DB::table('contacts')->insert([
            'user_id' => $userId, 
            'contact_id' => $contactId, 
            'status' => 'pending', 
            'created_at' => now(), 
            'updated_at' => now()
        ]);
        
        $this->sendSystemMessage($userId, $contactId, 'SYSTEM_FRIEND_REQUEST');

        return response()->json(['status' => 'pending']);
    });
}

private function sendSystemMessage($senderId, $receiverId, $type) {
    $msg = Message::create([
        'sender_id' => $senderId,
        'receiver_id' => $receiverId,
        'message' => $type,
    ]);
    broadcast(new \App\Events\MessageSentEvent([
        'id' => $msg->id,
        'sender_id' => User::find($senderId)->hashid,
        'receiver_id' => User::find($receiverId)->hashid,
        'message' => $type,
        'created_at' => now()->toIso8601String()
    ]));
}


public function getContacts(): JsonResponse 
{ 
    $userId = Auth::id();
    
    // 1. Получаем все записи отношений (и где мы инициатор, и где мы получатель)
    $contacts = DB::table('contacts')
        ->where('user_id', $userId)
        ->orWhere('contact_id', $userId)
        ->get();

    // 2. Выделяем ID всех собеседников
    $targetIds = $contacts->map(fn($row) => $row->user_id == $userId ? $row->contact_id : $row->user_id)->unique();
    
    // 3. Загружаем данные пользователей и агрегируем статистику
    $users = User::whereIn('id', $targetIds)->get()->map(function($u) use ($userId, $contacts) {
        // Ищем конкретную строку связи для этого пользователя
        $row = $contacts->where('user_id', $u->id)->where('contact_id', $userId)->first() 
               ?? $contacts->where('user_id', $userId)->where('contact_id', $u->id)->first();

        // ПРОФЕССИОНАЛЬНЫЙ ФИКС: Считаем точное количество непрочитанных
        // Это нужно для отрисовки цифры (например, "5") над мессенджером
        $unreadCount = Message::where('sender_id', $u->id)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->count();

        return [
            'id' => $u->hashid,
            'name' => $u->name, 
            'is_online' => $u->isOnline(), 
            'last_seen_human' => $u->getLastSeenForHumans(),
            'status' => $row->status, // 'pending' или 'accepted'
            
            // Логика: запрос считается входящим, если contact_id — это МЫ и статус еще 'pending'
            'is_incoming' => ($row->contact_id == $userId && $row->status === 'pending'), 
            
            // Числовой счетчик для бейджа
            'unread_count' => $unreadCount,
            'has_new_message' => $unreadCount > 0
        ];
    })
    // Сортировка: Сначала новые запросы в друзья, затем те кто онлайн
    ->sort(function($a, $b) {
        if ($a['is_incoming'] !== $b['is_incoming']) {
            return $b['is_incoming'] ? 1 : -1;
        }
        if ($a['is_online'] !== $b['is_online']) {
            return $b['is_online'] ? 1 : -1;
        }
        return 0;
    })
    ->values(); 

    return response()->json(['contacts' => $users]);
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

    // Собираем всех партнеров, с кем были пересечения в interactions в обе стороны,
    // исключая тех, кто заблокирован
    $partnerIds = DB::table('interactions')
        ->where('user_id', $userId)
        ->orWhere('partner_id', $userId)
        ->get()
        ->map(function($row) use ($userId) {
            return $row->user_id == $userId ? $row->partner_id : $row->user_id;
        })
        ->unique();

    $history = collect();

    foreach ($partnerIds as $partnerId) {
        // 1. Проверяем черный список
        $isBlocked = DB::table('blocks')
            ->where(fn($q) => $q->where('blocker_id', $userId)->where('blocked_id', $partnerId))
            ->orWhere(fn($q) => $q->where('blocker_id', $partnerId)->where('blocked_id', $userId))
            ->exists();

        if ($isBlocked) continue;

        // 2. Проверяем, является ли пользователь ПРИНЯТЫМ другом
        $isAcceptedFriend = DB::table('contacts')
            ->where(function($q) use ($userId, $partnerId) {
                $q->where('user_id', $userId)->where('contact_id', $partnerId);
            })
            ->orWhere(function($q) use ($userId, $partnerId) {
                $q->where('user_id', $partnerId)->where('contact_id', $userId);
            })
            ->where('status', 'accepted')
            ->exists();

        // Если это уже друг, в истории звонков рулетки он не нужен (он есть в контактах)
        if ($isAcceptedFriend) continue;

        $u = User::find($partnerId);
        if (!$u) continue;

        // Берем время последнего взаимодействия
        $lastInteraction = DB::table('interactions')
            ->where(function($q) use ($userId, $partnerId) {
                $q->where('user_id', $userId)->where('partner_id', $partnerId);
            })
            ->orWhere(function($q) use ($userId, $partnerId) {
                $q->where('user_id', $partnerId)->where('partner_id', $userId);
            })
            ->orderByDesc('last_at')
            ->first();

        $contactEntry = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $partnerId)
            ->first();

        $history->push([
            'id' => $u->hashid,
            'name' => $u->name,
            'is_online' => $u->isOnline(),
            'last_seen_human' => $u->getLastSeenForHumans(),
            'last_met_diff' => $lastInteraction ? \Carbon\Carbon::parse($lastInteraction->last_at)->diffForHumans() : 'Just now',
            'last_at_raw' => $lastInteraction ? $lastInteraction->last_at : now(),
            'is_blocked' => false,
            'is_pending' => $contactEntry && $contactEntry->status === 'pending',
            'level' => $u->level ?? 1,
            'rank_name' => $u->rank_name ?? 'Newbie'
        ]);
    }

    // Сортируем по времени последнего общения (свежие сверху)
    $sortedHistory = $history->sortByDesc('last_at_raw')->values()->map(function($item) {
        unset($item['last_at_raw']); // убираем системное поле
        return $item;
    });

    return response()->json(['history' => $sortedHistory]);
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
    // Сохраняем в кэш чистый JSON-текст
    $json = \Illuminate\Support\Facades\Cache::remember('leaderboard_top_50_v8', 600, function () {
        return User::where(function ($query) {
                $query->whereNull('banned_until')
                      ->orWhere('banned_until', '<', now());
            })
            ->orderBy('xp', 'desc')
            ->take(50)
            ->get()
            ->toJson();
    });

    // Превращаем JSON обратно в коллекцию стандартных объектов (stdClass)
    $topUsers = collect(json_decode($json));

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
    $request->validate(['senderId' => 'required|string']);
    $senderId = $this->decodeId($request->senderId); // <-- ДЕКОДИРУЕМ HASHID
    $myId = Auth::id();

    if ($senderId === 0) {
        return response()->json(['error' => 'Invalid sender ID'], 422);
    }

    DB::transaction(function() use ($senderId, $myId) {
        DB::table('contacts')->updateOrInsert(
            ['user_id' => $myId, 'contact_id' => $senderId],
            ['status' => 'accepted', 'updated_at' => now()]
        );
        
        DB::table('contacts')
            ->where('user_id', $senderId)
            ->where('contact_id', $myId)
            ->update(['status' => 'accepted', 'updated_at' => now()]);

        $msg = Message::create([
            'sender_id' => $myId,
            'receiver_id' => $senderId,
            'message' => 'SYSTEM_FRIEND_ACCEPTED',
        ]);

        broadcast(new \App\Events\MessageSentEvent([
            'id' => $msg->id,
            'sender_id' => Auth::user()->hashid,
            'receiver_id' => User::find($senderId)->hashid,
            'message' => 'SYSTEM_FRIEND_ACCEPTED',
            'created_at' => now()->toIso8601String()
        ]));
    });

    return response()->json(['status' => 'success']);
}

public function declineFriend(Request $request): JsonResponse
{
    $senderId = $this->decodeId($request->senderId); // Тот кто просил дружбу
    $myId = Auth::id();

    // При отклонении мы просто удаляем запись. 
    // Это позволяет человеку отправить запрос снова позже (как вы просили).
    DB::table('contacts')
        ->where('user_id', $senderId)
        ->where('contact_id', $myId)
        ->delete();

    return response()->json(['status' => 'declined']);
}

public function removeContact(Request $request): JsonResponse 
{
    $request->validate(['contactId' => 'required|string']);
    $contactId = $this->decodeId($request->contactId);
    $userId = Auth::id();

    if ($contactId === 0) {
        return response()->json(['error' => 'Invalid contact ID'], 422);
    }

    DB::table('contacts')
        ->where(function($q) use ($userId, $contactId) {
            $q->where('user_id', $userId)->where('contact_id', $contactId);
        })
        ->orWhere(function($q) use ($userId, $contactId) {
            $q->where('user_id', $contactId)->where('contact_id', $userId);
        })
        ->delete();

    // Отправляем сигнал собеседнику, что связь разорвана
    broadcast(new \App\Events\WebRTCSignalEvent($contactId, [
        'type' => 'contact-removed',
        'contactId' => Auth::user()->hashid
    ]));

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

public function markAsRead(Request $request): JsonResponse
{
    $request->validate(['contactId' => 'required|string']);
    
    // Декодируем Hashid в числовой ID
    $contactId = $this->decodeId($request->contactId);
    $userId = Auth::id();

    if ($contactId === 0) {
        return response()->json(['error' => 'Invalid contact ID'], 422);
    }

    // Обновляем статус всех входящих непрочитанных сообщений от этого пользователя
    Message::where('sender_id', $contactId)
        ->where('receiver_id', $userId)
        ->where('is_read', false)
        ->update(['is_read' => true]);

    return response()->json(['status' => 'success']);
}

}