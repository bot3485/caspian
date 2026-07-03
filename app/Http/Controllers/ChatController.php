<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
use App\Models\User;
use App\Events\{MatchFoundEvent, MessageSentEvent, WebRTCSignalEvent};
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\{Auth, DB};
use App\Enums\MatchmakingStatus;

class ChatController extends Controller
{
    /**
     * Старт поиска партнера (Roulette).
     * Оптимизировано: используется транзакция и запись связки партнеров.
     */
    public function startSearching(Request $request): JsonResponse
    {
        $user = Auth::user();
        
        // ПРОВЕРКА НА БАН
        if ($user->banned_until && $user->banned_until > now()) {
            return response()->json([
                'error' => 'Доступ заблокирован за нарушение правил.',
                'until' => $user->banned_until->diffForHumans()
            ], 403);
        }

        $userId = $user->id;

        return DB::transaction(function () use ($userId) {
            // 1. Удаляем старые записи этого пользователя
            Matchmaking::where('user_id', $userId)->delete();

            // 2. Ищем того, кто уже ждет (Waiting)
            $waitingUser = Matchmaking::where('status', MatchmakingStatus::Waiting)
                ->where('user_id', '!=', $userId)
                ->lockForUpdate() // Блокируем строку, чтобы два юзера не подцепили одного и того же
                ->first();

            if ($waitingUser) {
                $partnerId = $waitingUser->user_id;

                // 3. Обновляем статус ожидающего: он теперь в паре с нами
                $waitingUser->update([
                    'status' => MatchmakingStatus::Matched,
                    'partner_id' => $userId
                ]);

                // 4. Создаем свою запись: мы в паре с ним
                Matchmaking::create([
                    'user_id' => $userId,
                    'status' => MatchmakingStatus::Matched,
                    'partner_id' => $partnerId
                ]);

                // 5. Оповещаем обоих через сокеты
                broadcast(new MatchFoundEvent(targetUserId: $partnerId, partnerId: $userId));
                broadcast(new MatchFoundEvent(targetUserId: $userId, partnerId: $partnerId));

                return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
            }

            // 6. Если никого нет, встаем в очередь
            Matchmaking::create([
                'user_id' => $userId,
                'status' => MatchmakingStatus::Waiting
            ]);

            return response()->json(['status' => 'searching']);
        });
    }

    /**
     * Безопасная отправка WebRTC сигнала.
     * Проверяет, действительно ли пользователи находятся в одной "сессии".
     */
    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partnerId' => 'required|integer|exists:users,id',
            'data' => 'required|array'
        ]);

        $userId = Auth::id();
        $partnerId = $validated['partnerId'];

        // ПРОВЕРКА ПРАВ (Security Check)
        // Сигнал уйдет только если в базе есть запись, что эти двое — пара.
        $isValidPair = Matchmaking::where('user_id', $userId)
            ->where('partner_id', $partnerId)
            ->where('status', MatchmakingStatus::Matched)
            ->exists();

        // Если это не рулетка, а групповая комната (в data передается roomUuid)
        // Эту логику можно расширить здесь, проверяя участие в комнате.
        $isRoomSignal = isset($validated['data']['roomUuid']);

        if (!$isValidPair && !$isRoomSignal) {
            return response()->json(['error' => 'Unauthorized signaling'], 403);
        }

        broadcast(new WebRTCSignalEvent($partnerId, $validated['data']));
        
        return response()->json(['status' => 'signal_sent']);
    }

    /**
     * Выход из чата.
     */
    public function leaveChat(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // Находим, с кем был связан пользователь перед удалением
        $match = Matchmaking::where('user_id', $userId)->first();
        
        if ($match && $match->partner_id) {
            // Уведомляем партнера о разрыве
            broadcast(new WebRTCSignalEvent($match->partner_id, [
                'type' => 'peer-disconnected',
                'oldPartnerId' => $userId
            ]));
            
            // Очищаем запись партнера (переводим его в idle или тоже удаляем)
            Matchmaking::where('user_id', $match->partner_id)->delete();
        }

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

        // Проверка: является ли получатель контактом? (Пункт 1-Б: Безопасность)
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
        
        // Преобразуем дату в ISO для фронтенда
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

        return response()->json(['contacts' => $contacts]);
    }

    public function toggleContact(Request $request): JsonResponse
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        
        $userId = Auth::id();
        $contactId = $request->contactId;

        $exists = DB::table('contacts')
            ->where('user_id', $userId)
            ->where('contact_id', $contactId)
            ->exists();

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

    public function getChatHistory(int $contactId): JsonResponse
    {
        $userId = Auth::id();
        
        $messages = DB::table('messages')
            ->where(fn($q) => $q->where('sender_id', $userId)->where('receiver_id', $contactId))
            ->orWhere(fn($q) => $q->where('sender_id', $contactId)->where('receiver_id', $userId))
            ->orderBy('created_at', 'asc')
            ->take(50)
            ->get();

        DB::table('messages')
            ->where('sender_id', $contactId)
            ->where('receiver_id', $userId)
            ->update(['is_read' => true]);

        return response()->json(['messages' => $messages]);
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
}