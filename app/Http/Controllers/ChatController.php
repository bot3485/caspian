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
    // 1. Валидация входных данных
    $request->validate(['contactId' => 'required|integer|exists:users,id']);
    
    // 2. Определение участников
    $receiverId = (int)$request->contactId;
    $senderId = (int)Auth::id();

    // Защита от звонка самому себе
    if ($senderId === $receiverId) {
        return response()->json(['error' => 'Self-call'], 400);
    }

    // 3. Проверка блокировок (Черный список)
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

    // 4. ПРОВЕРКА: Занят ли собеседник (уже находится вMatched статусе)
    $isBusy = Matchmaking::where('user_id', $receiverId)
        ->where('status', MatchmakingStatus::Matched)
        ->where('updated_at', '>=', now()->subSeconds(35)) 
        ->exists();

    if ($isBusy) {
        // Опционально: создаем запись о пропущенном вызове в сообщениях
        Message::create([
            'sender_id' => $senderId,
            'receiver_id' => $receiverId,
            'message' => '📞 Missed call (Receiver was busy)',
            'is_read' => false
        ]);
        
        return response()->json(['status' => 'busy', 'message' => 'User is busy']);
    }

    // 5. ПОДГОТОВКА: Выходим из текущих очередей рулетки, если они были
    $this->leaveChatAction->execute($senderId);

    // 6. СИСТЕМНЫЙ ДОСТУП (Redis): Разрешаем сигналинг в ОБЕ стороны.
    // Это критически важно сделать ДО отправки события, чтобы при получении оффера сервер уже знал, что это разрешено.
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$senderId}:{$receiverId}", 3600, "1");
    \Illuminate\Support\Facades\Redis::setex("allow_signal:{$receiverId}:{$senderId}", 3600, "1");

    // 7. СОСТОЯНИЕ В БД: Фиксируем статус звонка.
    // Это служит фолбэком для метода sendSignal, если Redis по какой-то причине не сработает мгновенно.
    Matchmaking::updateOrCreate(
        ['user_id' => $senderId],
        [
            'status' => MatchmakingStatus::Matched, 
            'partner_id' => $receiverId, 
            'updated_at' => now()
        ]
    );

    // 8. ОТПРАВКА СОБЫТИЯ: Уведомляем получателя о входящем вызове
    broadcast(new WebRTCSignalEvent($receiverId, [
        'type' => 'incoming-call',
        'fromName' => Auth::user()->name,
        'fromId' => $senderId
    ]));

    // 9. ОТВЕТ: Клиент (инициатор) получает статус 'calling' и запускает свою камеру/initPC
    return response()->json(['status' => 'calling']);
}

    public function getUserInfo(User $user): JsonResponse
    {
        return response()->json([
            'id' => $user->id,
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
        $validated = $request->validate([
            'receiver_id' => 'required|integer', 
            'message' => 'required|string'
        ]);

        // Принудительно пишем в лог, что пытаемся сохранить
        \Illuminate\Support\Facades\Log::info("Сохраняем сообщение в БД:", [
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message']
        ]);

        $message = Message::create([
            'sender_id' => Auth::id(),
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message']
        ]);

        \Illuminate\Support\Facades\Log::info("Сообщение успешно сохранено с ID: " . $message->id);

        broadcast(new MessageSentEvent($message->toArray()));

        return response()->json(['status' => 'sent', 'message' => $message]);
    }

    public function getChatHistory(int $contactId): JsonResponse
    {
        $userId = Auth::id();
        
        // 1. Сначала забираем 100 САМЫХ СВЕЖИХ сообщений (сортируем по убыванию id/created_at)
        $messages = Message::where(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })->orWhere(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            })
            ->orderBy('id', 'desc') // Берем сначала самые новые
            ->take(100)
            ->get()
            ->reverse() // Переворачиваем массив обратно, чтобы в чате они шли хронологически (сверху вниз)
            ->values(); // Сбрасываем ключи массива для корректного JSON

        // 2. Помечаем входящие от этого контакта как прочитанные
        Message::where('sender_id', $contactId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
    }

    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

public function addContact(Request $request): JsonResponse 
{
    $contactId = (int)$request->contactId;
    $userId = Auth::id();

    if ($userId === $contactId) return response()->json(['error' => 'Self-addition'], 400);

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
    
    // 1. Находим ID всех людей, с которыми есть связь (в любую сторону)
    $contactRows = DB::table('contacts')
        ->where('user_id', $userId)
        ->orWhere('contact_id', $userId)
        ->get();

    // Собираем уникальные ID партнеров
    $targetIds = $contactRows->map(function($row) use ($userId) {
        return $row->user_id == $userId ? $row->contact_id : $row->user_id;
    })->unique();

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
                'id' => $u->id, 
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
                'id' => $record->id,
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

    return response()->json(['status' => 'success']);
}

}