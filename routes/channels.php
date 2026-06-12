<?php

use Illuminate\Support\Facades\Broadcast;

// Публичный канал: возвращаем true, чтобы любой мог слушать
Broadcast::channel('test-channel', function () {
    return true; 
});