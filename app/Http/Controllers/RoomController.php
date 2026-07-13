<?php

namespace App\Http\Controllers;

use App\Models\Room;
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\Support\Facades\{Auth, Hash, Session, DB};
use Illuminate\View\View;

class RoomController extends Controller
{
    public function index(): View
    {
        $userId = Auth::id();
        
        // Делаем фильтрацию на уровне БД, а не коллекции
        $rooms = Room::with('creator')
            ->where(function($q) use ($userId) {
                $q->where('is_public', true)
                ->orWhere('creator_id', $userId);
            })
            ->latest()
            ->get();

        $userHasRoom = Room::where('creator_id', $userId)->exists();

        return view('rooms.index', compact('rooms', 'userHasRoom'));
    }

    public function store(Request $request): JsonResponse
    {
        $userId = Auth::id();

    if (Room::where('creator_id', Auth::id())->exists()) {
        return response()->json(['message' => 'You already have an active Space. Delete it to create a new one.'], 403);
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

    public function show(string $uuid): View|RedirectResponse
    {
        $room = Room::where('uuid', $uuid)->with('creator')->firstOrFail();
        $hasPassword = !empty($room->getRawOriginal('password'));
        
        if (!$hasPassword) {
            return view('rooms.show', compact('room'));
        }

        if (Session::has("room_auth_{$room->uuid}") || $room->creator_id === Auth::id()) {
            return view('rooms.show', compact('room'));
        }

        return view('rooms.auth', compact('room'));
    }

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

        return response()->json(['message' => 'Security check failed. Wrong password.'], 403);
    }

    public function destroy(string $uuid): JsonResponse
    {
        $room = Room::where('uuid', $uuid)->where('creator_id', Auth::id())->firstOrFail();
        $room->delete();
        return response()->json(['status' => 'success']);
    }

public function syncOccupancy(Request $request, string $uuid): JsonResponse
{
    // Beacon присылает данные в виде строки, поэтому приводим к int
    $count = (int) $request->input('count', 0);
    
    $room = Room::where('uuid', $uuid)->firstOrFail();

    // Обновляем количество участников
    $room->current_occupancy = $count < 0 ? 0 : $count;
    $room->touch(); // Обновляем updated_at, чтобы Schedule не считал комнату мертвой
    $room->save();
    
    // Оповещаем всех в лобби (на главной странице)
    broadcast(new \App\Events\RoomOccupancyUpdated($uuid, $room->current_occupancy))->toOthers();

    return response()->json(['status' => 'ok', 'count' => $room->current_occupancy]);
}
}