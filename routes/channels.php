<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;



Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Наш новый глобальный Presence канал для отслеживания статусов онлайн
Broadcast::channel('online-status', function ($user) {
    return [
        'id' => $user->id,
        'name' => $user->name
    ];
});

Broadcast::channel('room.{uuid}', function ($user, $uuid) {
    // 1. Ищем комнату
    $room = \App\Models\Room::where('uuid', $uuid)->first();
    
    // Если комнаты нет в базе — доступ запрещен
    if (!$room) {
        return false;
    }

    // 2. Проверка лимита (6 человек)
    // Добавляем проверку: если пользователь УЖЕ в комнате (обновляет страницу), 
    // или он создатель — пускаем всегда.
    $isFull = $room->current_occupancy >= 6;
    $isCreator = (int)$room->creator_id === (int)$user->id;

    if ($isFull && !$isCreator) {
        // Проверяем, не является ли он уже частью этой комнаты (чтобы не заблокировать при F5)
        // Но для простоты сейчас просто разрешим вход, если он создатель
        return false;
    }

    // ВАЖНО: Возвращаем массив с Hashid
    return [
        'id'   => $user->hashid, // Это строка-хеш
        'name' => $user->name
    ];
});
