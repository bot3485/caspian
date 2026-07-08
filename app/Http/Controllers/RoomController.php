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
        $rooms = Room::where('is_public', true)
            ->with('creator')
            ->latest()
            ->get();

        // Общий онлайн во всех комнатах
        $totalOnlineInSpaces = $rooms->sum('current_occupancy');

        return view('rooms.index', compact('rooms', 'totalOnlineInSpaces'));
    }

    /**
     * Store a newly created room in storage.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['is_public' => $request->boolean('is_public')]);
        
        $validated = $request->validate([
            'title' => 'required|string|max:50|min:3',
            'password' => 'nullable|string|min:4',
            'is_public' => 'required|boolean',
        ]);

        $userId = Auth::id();

        // Лимит: не более 5 активных комнат на одного пользователя
        $existingCount = Room::where('creator_id', $userId)->count();
        if ($existingCount >= 5) {
            return response()->json([
                'message' => 'Вы достигли лимита созданных комнат (макс: 5).'
            ], 403);
        }

        return DB::transaction(function () use ($validated, $userId) {
            $room = Room::create([
                'title' => $validated['title'],
                'password' => $validated['password'] ?: null, 
                'is_public' => $validated['is_public'],
                'creator_id' => $userId,
            ]);

            // Автоматически авторизуем создателя для доступа к собственной комнате
            Session::put("room_auth_{$room->uuid}", true);

            return response()->json([
                'status' => 'success', 
                'redirect' => route('rooms.show', $room->uuid)
            ]);
        });
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
}