<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Session, DB};
use Illuminate\View\View;

class RoomController extends Controller
{
    /**
     * Показ списка публичных комнат.
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
     * Создание новой комнаты.
     */
    public function store(Request $request): JsonResponse
    {
        $request->merge(['is_public' => $request->boolean('is_public')]);
        
        $validated = $request->validate([
            'title' => 'required|string|max:50|min:3',
            'password' => 'nullable|string|min:4',
            'is_public' => 'required|boolean',
        ]);

        return DB::transaction(function () use ($validated) {
            $userId = Auth::id();

            // Удаляем старые комнаты пользователя для чистоты базы
            Room::where('creator_id', $userId)->delete();

            $room = Room::create([
                'title' => $validated['title'],
                'password' => $validated['password'] ?: null, 
                'is_public' => $validated['is_public'],
                'creator_id' => $userId,
            ]);

            // Автоматически даем доступ создателю
            Session::put("room_auth_{$room->uuid}", true);

            return response()->json([
                'status' => 'success', 
                'redirect' => route('rooms.show', $room->uuid)
            ]);
        });
    }

    /**
     * Показ комнаты (с проверкой доступа).
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();

        // 1. Если комната открытая (нет хеша пароля)
        if (empty($room->getRawOriginal('password'))) {
            return view('rooms.show', compact('room'));
        }

        // 2. Если пароль есть, проверяем сессию
        if (Session::has("room_auth_{$room->uuid}")) {
            return view('rooms.show', compact('room'));
        }

        // 3. Если создатель заходит в свою комнату
        if ($room->creator_id === Auth::id()) {
            Session::put("room_auth_{$room->uuid}", true);
            return view('rooms.show', compact('room'));
        }

        // Иначе — на страницу ввода пароля
        return view('rooms.auth', compact('room'));
    }

    /**
     * Обработка попытки входа в защищенную комнату.
     */
    public function join(Request $request, string $uuid): JsonResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();
        $storedHash = $room->getRawOriginal('password');

        if (empty($storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        $request->validate([
            'password' => 'required|string'
        ]);

        if (Hash::check($request->password, $storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        return response()->json([
            'message' => 'Неверный пароль. Попробуйте еще раз.'
        ], 403);
    }
}