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
    $room = \App\Models\Room::where('uuid', $uuid)->first();
    
    if (!$room) return false;

    // Если в комнате уже 6 человек И текущий юзер — не создатель (создатель может зайти всегда)
    if ($room->current_occupancy >= 6 && $room->creator_id !== $user->id) {
        return false; 
    }

    return ['id' => $user->id, 'name' => $user->name];
});