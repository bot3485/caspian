<?php

use Illuminate\Support\Facades\Broadcast;


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
