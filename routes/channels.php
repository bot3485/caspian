<?php

use Illuminate\Support\Facades\Broadcast;

// Публичный канал: возвращаем true, чтобы любой мог слушать
Broadcast::channel('test-channel', function () {
    return true; 
});


Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

