<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Models\User;
use App\Actions\FindPartner;
use App\Events\{MatchFoundEvent, MessageSentEvent, WebRTCSignalEvent};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, DB, Redis};
use App\Enums\MatchmakingStatus;
use Carbon\Carbon; 

class ChatController extends Controller
{
    /**
     * Старт поиска партнера (Roulette).
     * Теперь использует Redis для очереди и MySQL для фиксации активной пары.
     */
    public function startSearching(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // 1. ПРОВЕРКА НА БАН
        if ($user->banned_until && $user->banned_until > now()) {
            return response()->json([
                'error' => 'Доступ заблокирован за нарушение правил.',
                'until' => $user->banned_until->diffForHumans()
            ], 403);
        }

        $userId = $user->id;

        // 2. Очищаем старые записи в БД (фактически завершаем старые сессии)
        Matchmaking::where('user_id', $userId)->delete();

        // 3. Вызываем логику поиска через Redis
        $finder = new FindPartner();
        $partnerId = $finder->execute($userId);

        if ($partnerId) {
            // 4. Если партнер найден, фиксируем связь в MySQL для безопасности сигналов
            // Это нужно, чтобы метод sendSignal знал, что этим двоим МОЖНО общаться
            Matchmaking::create([
                'user_id' => $userId,
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $partnerId
            ]);

            Matchmaking::create([
                'user_id' => $partnerId,
                'status' => MatchmakingStatus::Matched,
                'partner_id' => $userId
            ]);

            return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
        }

        return response()->json(['status' => 'searching']);
    }

    /**
     * Безопасная отправка WebRTC сигнала.
     */
    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partnerId' => 'required|integer|exists:users,id',
            'data' => 'required|array'
        ]);

        $userId = Auth::id();
        $partnerId = $validated['partnerId'];

        // ПРОВЕРКА ПРАВ: Сигнал уйдет только если в MySQL есть запись об их паре
        $isValidPair = Matchmaking::where('user_id', $userId)
            ->where('partner_id', $partnerId)
            ->where('status', MatchmakingStatus::Matched)
            ->exists();

        $isRoomSignal = isset($validated['data']['roomUuid']);

        if (!$isValidPair && !$isRoomSignal) {
            return response()->json(['error' => 'Unauthorized signaling'], 403);
        }

        broadcast(new WebRTCSignalEvent($partnerId, $validated['data']));
        
        return response()->json(['status' => 'signal_sent']);
    }

    /**
     * Выход из чата / Пропуск партнера.
     */
    public function leaveChat(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // 1. Удаляем из очереди Redis (если он там был)
        (new FindPartner())->removeFromQueue($userId);

        // 2. Находим текущего партнера в БД
        $match = Matchmaking::where('user_id', $userId)->first();
        
        if ($match && $match->partner_id) {
            // Уведомляем партнера, что мы ушли
            broadcast(new WebRTCSignalEvent($match->partner_id, [
                'type' => 'peer-disconnected',
                'oldPartnerId' => $userId
            ]));
            
            // Удаляем связь у партнера
            Matchmaking::where('user_id', $match->partner_id)->delete();
        }

        // Удаляем свою запись
        Matchmaking::where('user_id', $userId)->delete();
        
        return response()->json(['status' => 'left']);
    }

    /**
     * Отправка сообщения в мессенджере (постоянные контакты).
     */
    public function sendMessage(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'receiver_id' => 'required|integer|exists:users,id',
            'message' => 'required|string|max:1000'
        ]);

        $userId = Auth::id();

        $isContact = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $validated['receiver_id'])
            ->exists();

        if (!$isContact) {
            return response()->json(['error' => 'You can only message your contacts'], 403);
        }

        $messageData = [
            'sender_id' => $userId,
            'receiver_id' => $validated['receiver_id'],
            'message' => $validated['message'],
            'is_read' => false,
            'created_at' => now(),
            'updated_at' => now()
        ];

        $messageData['id'] = DB::table('messages')->insertGetId($messageData);
        $messageData['created_at'] = $messageData['created_at']->toIso8601String();

        broadcast(new MessageSentEvent($messageData))->toOthers();

        return response()->json(['status' => 'sent', 'message' => $messageData]);
    }

    // --- Методы для работы со списком контактов ---

    public function getContacts(): JsonResponse
    {
        $contacts = DB::table('contacts')
            ->where('contacts.user_id', Auth::id())
            ->join('users', 'users.id', '=', 'contacts.contact_id')
            ->select('users.id', 'users.name', 'users.last_seen')
            ->get();

        $redisData = \Illuminate\Support\Facades\Redis::hgetall('users_last_seen');

        $contacts = $contacts->map(function ($contact) use ($redisData) {
            $userIdStr = (string)$contact->id;

            // Определяем финальный Timestamp
            if (isset($redisData[$userIdStr])) {
                // Если есть в Redis — берем оттуда
                $contact->last_seen_timestamp = (int)$redisData[$userIdStr];
            } elseif ($contact->last_seen) {
                // Если нет в Redis — берем из БД и переводим в секунды (UTC)
                $contact->last_seen_timestamp = \Carbon\Carbon::parse($contact->last_seen)->timestamp;
            } else {
                $contact->last_seen_timestamp = null;
            }

            return $contact;
        });

        return response()->json(['contacts' => $contacts]);
    }

    public function toggleContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $userId = Auth::id();
        $contactId = $request->contactId;

        $exists = DB::table('contacts')->where('user_id', $userId)->where('contact_id', $contactId)->exists();

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
        $userId = Auth::id();
        $beforeId = $request->query('before_id');

        $query = DB::table('messages')
            ->where(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $userId)->where('receiver_id', $contactId);
            })
            ->orWhere(function($q) use ($userId, $contactId) {
                $q->where('sender_id', $contactId)->where('receiver_id', $userId);
            });

        if ($beforeId) {
            $query->where('id', '<', $beforeId);
        }

        // Берем 30 последних
        $messages = $query->orderBy('id', 'desc')
            ->take(30)
            ->get()
            ->reverse()
            ->values();

        return response()->json([
            'messages' => $messages,
            'has_more' => count($messages) === 30
        ]);
    }
    
    public function callContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        
        broadcast(new WebRTCSignalEvent($request->contactId, [
            'type' => 'incoming-direct-call',
            'callerId' => Auth::id(),
            'callerName' => Auth::user()->name
        ]));

        return response()->json(['status' => 'calling']);
    }

    public function sendTypingSignal(Request $request): JsonResponse
    {
        $request->validate(['receiver_id' => 'required|integer']);
        
        broadcast(new WebRTCSignalEvent($request->receiver_id, [
            'type' => 'typing',
            'from' => Auth::id()
        ]));

        return response()->json(['status' => 'ok']);
    }
}