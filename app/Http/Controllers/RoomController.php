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
            ->with('creator') // Жадная загрузка создателя
            ->withCount(['creator as participants_count']) // Можно расширить для подсчета реальных людей через Redis
            ->latest()
            ->get();

        return view('rooms.index', compact('rooms'));
    }

    /**
     * Создание новой комнаты.
     * Оптимизация: атомарная транзакция и очистка старых комнат пользователя.
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

            // 1. Удаляем все комнаты, которые этот пользователь создал ранее (дисциплина базы)
            Room::where('creator_id', $userId)->delete();

            // 2. Создаем новую комнату
            // Пароль хешируется автоматически через casts в модели Room
            $room = Room::create([
                'title' => $validated['title'],
                'password' => $validated['password'] ?: null, 
                'is_public' => $validated['is_public'],
                'creator_id' => $userId,
            ]);

            // 3. Автоматически даем создателю доступ к своей комнате
            Session::put("room_auth_{$room->uuid}", true);

            return response()->json([
                'status' => 'success', 
                'redirect' => route('rooms.show', $room->uuid)
            ]);
        });
    }

    /**
     * Показ комнаты. Проверяет наличие пароля и авторизацию в сессии.
     */
    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->firstOrFail();

        // Если пароля нет (публичная комната)
        if (empty($room->getRawOriginal('password'))) {
            return view('rooms.show', compact('room'));
        }

        // Если пароль есть, проверяем, вводил ли его пользователь ранее
        if (Session::has("room_auth_{$room->uuid}")) {
            return view('rooms.show', compact('room'));
        }

        // Если создатель комнаты заходит в неё — пускаем без пароля (на случай сброса сессии)
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

        // 1. Если комната открытая
        if (empty($storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        // 2. Валидация ввода
        $request->validate([
            'password' => 'required|string'
        ]);

        // 3. Проверка пароля
        if (Hash::check($request->password, $storedHash)) {
            Session::put("room_auth_{$room->uuid}", true);
            return response()->json(['status' => 'access_granted']);
        }

        return response()->json([
            'message' => 'Неверный пароль. Попробуйте еще раз.'
        ], 403);
    }

    /**
     * Удаление комнаты (принудительное закрытие создателем).
     */
    public function destroy(string $uuid): RedirectResponse
    {
        $room = Room::where('uuid', $uuid)
            ->where('creator_id', Auth::id())
            ->firstOrFail();

        $room->delete();

        return redirect()->route('rooms.index')->with('status', 'Комната успешно закрыта');
    }
}