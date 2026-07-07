<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Session, DB};
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Список публичных пространств.
     */
    public function index(): View
    {
        $rooms = Room::where('is_public', true)
            ->with('creator') 
            ->latest()
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    /**
     * Создание комнаты с проверкой лимита.
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

        // Проверка лимита: не более 5 активных комнат на пользователя
        $existingCount = Room::where('creator_id', $userId)->count();
        if ($existingCount >= 5) {
            return response()->json([
                'message' => 'Вы достигли лимита (5 комнат). Удалите старые, чтобы создать новую.'
            ], 403);
        }

        return DB::transaction(function () use ($validated, $userId) {
            $room = Room::create([
                'title' => $validated['title'],
                'password' => $validated['password'] ?: null, 
                'is_public' => $validated['is_public'],
                'creator_id' => $userId,
            ]);

            // Сразу даем доступ создателю
            Session::put("room_auth_{$room->uuid}", true);

            return response()->json([
                'status' => 'success', 
                'redirect' => route('rooms.show', $room->uuid)
            ]);
        });
    }

    /**
     * Просмотр комнаты.
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();

        // Если пароля нет вообще
        if (empty($room->getRawOriginal('password'))) {
            return view('rooms.show', compact('room'));
        }

        // Если есть пароль, проверяем сессию или авторство
        if (Session::has("room_auth_{$room->uuid}") || $room->creator_id === Auth::id()) {
            return view('rooms.show', compact('room'));
        }

        return view('rooms.auth', compact('room'));
    }

    /**
     * Вход по паролю.
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

        return response()->json(['message' => 'Неверный пароль'], 403);
    }
}