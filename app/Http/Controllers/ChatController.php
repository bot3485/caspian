<?php

namespace App\Http\Controllers;

use App\Models\Matchmaking;
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

    /**
     * Начало поиска партнера.
     */
    public function startSearching(Request $request): JsonResponse
    {
        $userId = Auth::id();
        
        // 1. Принудительно выходим из старых чатов перед новым поиском
        $this->leaveChatAction->execute($userId);

        // 2. Создаем запись в очереди
        Matchmaking::create([
            'user_id' => $userId,
            'status' => MatchmakingStatus::Searching
        ]);

        // 3. Запускаем алгоритм подбора (с учетом интересов)
        $partnerId = $this->findPartnerAction->execute($userId);

        if ($partnerId) {
            return response()->json(['status' => 'matched', 'partnerId' => $partnerId]);
        }

        return response()->json(['status' => 'searching']);
    }

    /**
     * Передача WebRTC сигналов между браузерами.
     */
    public function sendSignal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'partnerId' => 'required|integer',
            'data' => 'required|array'
        ]);

        $senderId = Auth::id();
        $receiverId = (int)$validated['partnerId'];
        $data = $validated['data'];
        $data['from'] = $senderId;

        // Проверка на бан
        if (Auth::user()->banned_until && Auth::user()->banned_until->isFuture()) {
            return response()->json(['error' => 'Account restricted'], 403);
        }

        // Проверка: разрешено ли этим пользователям общаться?
        $isAllowed = Redis::exists("allow_signal:{$senderId}:{$receiverId}") || 
                    Redis::exists("allow_signal:{$receiverId}:{$senderId}");

        if (!$isAllowed) {
            $isAllowed = Matchmaking::where('user_id', $senderId)->where('partner_id', $receiverId)->exists();
        }

        if (!$isAllowed) {
            // ФИКС: Разрешаем сигналы "пропуска" и "выхода" всегда
            $exitSignals = ['hang-up', 'peer-disconnected', 'peer-skipped'];
            if (isset($data['type']) && in_array($data['type'], $exitSignals)) {
                broadcast(new WebRTCSignalEvent($receiverId, $data));
                return response()->json(['status' => 'exit_signal_sent']);
            }
            return response()->json(['error' => 'Unauthorized Signal'], 403);
        }

        broadcast(new WebRTCSignalEvent($receiverId, $data));
        return response()->json(['status' => 'signal_sent']);
    }

    /**
     * Завершение чата.
     */
    public function leaveChat(Request $request): JsonResponse
    {
        $this->leaveChatAction->execute(Auth::id());
        return response()->json(['status' => 'left']);
    }

    /**
     * Работа с контактами (звездочка в чате).
     */
    public function addContact(Request $request): JsonResponse 
    {
        $request->validate(['contactId' => 'required|integer|exists:users,id']);
        $contactId = (int)$request->contactId;
        $userId = Auth::id();

        if ($userId === $contactId) return response()->json(['error' => 'Self-addition'], 400);

        DB::table('contacts')->updateOrInsert(
            ['user_id' => $userId, 'contact_id' => $contactId],
            ['updated_at' => now(), 'created_at' => now()]
        );

        return response()->json(['action' => 'added']);
    }

    public function getContacts(): JsonResponse 
    { 
        $contacts = DB::table('contacts')
            ->where('contacts.user_id', Auth::id())
            ->join('users', 'users.id', '=', 'contacts.contact_id')
            ->select('users.id', 'users.name', 'users.last_seen')
            ->get();
        return response()->json(['contacts' => $contacts]);
    }

    /**
     * Сигнал "Печатает..." для мессенджера (если DataChannel недоступен).
     */
    public function sendTypingSignal(Request $request): JsonResponse 
    {
        $request->validate(['receiver_id' => 'required|integer']);
        // Вещаем событие печати напрямую получателю
        broadcast(new \App\Events\UserTypingEvent($request->receiver_id, auth()->id()))->toOthers();
        return response()->json(['status' => 'sent']);
    }
}