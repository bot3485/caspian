<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Session, DB};
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Display a listing of public live spaces.
     * Мы загружаем создателей комнат и добавляем мета-данные для "живого" вида.
     */
    public function index(): View
    {
        $userId = Auth::id();

        // 1. Берем все публичные комнаты
        // 2. Добавляем свою комнату, даже если она приватная
        $rooms = Room::where('is_public', true)
            ->orWhere('creator_id', $userId)
            ->with('creator')
            ->latest()
            ->get()
            ->unique('id'); // Чтобы не дублировалась, если она и публичная, и ваша

        $userHasRoom = Room::where('creator_id', $userId)->exists();

        return view('rooms.index', compact('rooms', 'userHasRoom'));
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

        // КРИТИЧНО: Лимит 1 комната
        if (Room::where('creator_id', $userId)->exists()) {
            return response()->json([
                'message' => 'У вас уже есть созданная комната. Пожалуйста, удалите старую, чтобы создать новую.'
            ], 403);
        }

        $validated = $request->validate([
            'title' => 'required|string|max:40|min:3',
            'password' => 'nullable|string|min:4',
            'is_public' => 'required|boolean',
        ]);

        $room = Room::create([
            'title' => $validated['title'],
            'password' => $validated['password'],
            'is_public' => $validated['is_public'],
            'creator_id' => $userId,
        ]);

        Session::put("room_auth_{$room->uuid}", true);

        return response()->json(['status' => 'success', 'redirect' => route('rooms.show', $room->uuid)]);
    }

    /**
     * Display the specific room if authorized.
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->with('creator')->firstOrFail();

        // 1. Если комната публичная и без пароля — пускаем сразу
        $hasPassword = !empty($room->getRawOriginal('password'));
        
        if (!$hasPassword) {
            return view('rooms.show', compact('room'));
        }

        // 2. Если есть пароль, проверяем сессию или права владельца
        if (Session::has("room_auth_{$room->uuid}") || $room->creator_id === Auth::id()) {
            return view('rooms.show', compact('room'));
        }

        // 3. Если пароль нужен, но не введен — на страницу входа
        return view('rooms.auth', compact('room'));
    }

    /**
     * Handle password submission for private rooms.
     */
    public function join(Request $request, string $uuid): JsonResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();
        $storedHash = $room->getRawOriginal('password');

        if (empty($storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        $request->validate(['password' => 'required|string']);

        if (Hash::check($request->password, $storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        return response()->json([
            'message' => 'Предоставленный пароль не совпадает с настройками комнаты.'
        ], 403);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $room = Room::where('uuid', $uuid)
            ->where('creator_id', Auth::id())
            ->firstOrFail();

        $room->delete();

        return response()->json(['status' => 'success']);
    }

    public function syncOccupancy(Request $request, string $uuid): JsonResponse
    {
        $request->validate(['count' => 'required|integer|min:0|max:6']);
        
        $room = Room::where('uuid', $uuid)->firstOrFail();
        
        // Обновляем в БД
        $room->update(['current_occupancy' => $request->count]);
        
        // Отправляем событие на главную страницу (в Лобби)
        broadcast(new \App\Events\RoomOccupancyUpdated($uuid, $request->count));

        return response()->json(['status' => 'ok']);
    }

}