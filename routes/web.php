<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BrowserLogController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Video Chat Roulette & WebRTC Signals
Route::middleware('auth')->group(function () {
    Route::get('/chat', function () {
        return view('chat');
    })->name('chat');
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    Route::post('/chat/search', [ChatController::class, 'startSearching']);
    Route::post('/chat/leave', [ChatController::class, 'leaveChat']);
    Route::post('/chat/signal', [ChatController::class, 'sendSignal']);
    Route::get('/chat/signal', function () {
    return redirect()->route('chat'); // Если зашли GET-ом, просто кидаем в чат
    });
    
    // Contact List Management Endpoints
    Route::post('/chat/contact/check', [ChatController::class, 'checkContact']);
    Route::post('/chat/contact/toggle', [ChatController::class, 'toggleContact']);

    Route::get('/chat/contacts', [ChatController::class, 'getContacts']);
    Route::get('/chat/history/{contactId}', [ChatController::class, 'getChatHistory']);
    Route::post('/chat/message/send', [ChatController::class, 'sendMessage']);
    Route::post('/chat/message/typing', [ChatController::class, 'sendTypingSignal']);
    Route::post('/chat/contact/call', [ChatController::class, 'callContact']);

    //room
    Route::controller(RoomController::class)->group(function () {
        Route::get('/rooms', 'index')->name('rooms.index');
        Route::post('/rooms', 'store')->name('rooms.store');
        Route::get('/rooms/{uuid}', 'show')->name('rooms.show');
        Route::post('/rooms/{uuid}/join', 'join')->name('rooms.join');
    });
    
});

require __DIR__.'/auth.php';