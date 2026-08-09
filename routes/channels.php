<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Room;

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

// Канал для личных сигналов (WebRTC, сообщения)
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

// Канал для комнат (Presence)
Broadcast::channel('room.{uuid}', function ($user, $uuid) {
    $room = \App\Models\Room::where('uuid', $uuid)->first();
    if (!$room) return false;

    // ВАЖНО: 'id' должен быть СТРОКОЙ (Hashid)
    return [
        'id' => (string) $user->hashid, 
        'name' => $user->name
    ];
});