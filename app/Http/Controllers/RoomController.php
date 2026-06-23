<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Session};
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $rooms = Room::where('is_public', true)->with('creator')->latest()->get();
        return view('rooms.index', compact('rooms'));
    }

    public function store(Request $request): JsonResponse
    {
        $request->merge(['is_public' => $request->boolean('is_public')]);
        
        $validated = $request->validate([
            'title' => 'required|string|max:50',
            'password' => 'nullable|string',
            'is_public' => 'required|boolean',
        ]);

        // Очищаем старые комнаты
        Room::where('creator_id', Auth::id())->delete();

        // Если пароль пустой - передаем null, чтобы в базу не попала пустая строка
        $password = $validated['password'] ?: null;

        $room = Room::create([
            'title' => $validated['title'],
            'password' => $password, 
            'is_public' => $validated['is_public'],
            'creator_id' => Auth::id(),
        ]);

        Session::put("room_auth_{$room->uuid}", true);

        return response()->json(['status' => 'success', 'redirect' => route('rooms.show', $room->uuid)]);
    }

    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();

        // Если пароля нет ВООБЩЕ (публичная комната) - пускаем сразу
        // Проверяем оригинальное значение из базы
        if (empty($room->getRawOriginal('password'))) {
            return view('rooms.show', compact('room'));
        }

        // Если пароль есть, проверяем сессию
        if (Session::has("room_auth_{$room->uuid}")) {
            return view('rooms.show', compact('room'));
        }

        // Иначе - на страницу ввода пароля
        return view('rooms.auth', compact('room'));
    }

    public function join(Request $request, string $uuid): JsonResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();
        $storedHash = $room->getRawOriginal('password');

        // Если комната публичная - просто даем доступ
        if (empty($storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        // Если пароль ввели и он совпал
        if ($request->password && Hash::check($request->password, $storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        return response()->json(['message' => 'Неверный пароль'], 403);
    }
}