<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BrowserLogController;
use Illuminate\Support\Facades\Route;

// --- ПУБЛИЧНЫЕ РОУТЫ ---
Route::get('/', function () { return view('welcome'); });
Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

// --- РОУТЫ С АВТОРИЗАЦИЕЙ ---
Route::middleware(['auth', 'verified'])->group(function () {
    
Route::post('/rooms/{uuid}/sync-occupancy', [\App\Http\Controllers\RoomController::class, 'syncOccupancy'])->name('rooms.sync-occupancy');
   
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'stats' => [
                'total_users' => \App\Models\User::count(),
                // Считаем тех, кто в поиске или уже в паре
                'online_now' => \App\Models\Matchmaking::whereIn('status', ['searching', 'matched'])->count(),
                'total_minutes' => \App\Models\User::sum('total_minutes'),
                'active_rooms' => \App\Models\Room::where('is_public', true)->count(), // Ключ исправлен здесь
            ]
        ]);
    })->name('dashboard');

    Route::get('/leaderboard', function () { return view('leaderboard'); })->name('leaderboard');

    // Пульс (Heartbeat) для обновления last_seen
    Route::post('/ping', function () { return response()->json(['status' => 'pong']); })->name('ping');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    Route::prefix('chat')->group(function () {
        Route::get('/', function () { return view('chat'); })->name('chat');
        Route::post('/search', [ChatController::class, 'startSearching']);
        Route::post('/leave', [ChatController::class, 'leaveChat']);
        Route::post('/signal', [ChatController::class, 'sendSignal']);
        
        Route::get('/contacts', [ChatController::class, 'getContacts']);
        Route::post('/contact/add', [ChatController::class, 'addContact']); // Toggle в контроллере
        Route::post('/contact/call', [ChatController::class, 'callContact']);
        
        Route::get('/history/{contactId}', [ChatController::class, 'getChatHistory']);
        Route::post('/message/send', [ChatController::class, 'sendMessage']);
        Route::post('/message/typing', [ChatController::class, 'sendTypingSignal']);
    });

    Route::controller(RoomController::class)->prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{uuid}', 'show')->name('show');
        Route::post('/{uuid}/join', 'join')->name('join');
        Route::delete('/{uuid}', 'destroy')->name('destroy');
    });
});

require __DIR__.'/auth.php';