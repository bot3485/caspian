<?php

use Illuminate\Support\Facades\Route;
use App\Events\TestEvent;

Route::get('/', function () {
    return view('welcome');
});




Route::get('/test-broadcast', function () {
    event(new TestEvent());
    return "Событие отправлено!";
});