<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BrowserLogController;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use App\Models\Matchmaking;
use App\Models\Room;

// --- ПУБЛИЧНЫЕ РОУТЫ ---
Route::get('/', function () { 
    return view('welcome'); 
});

// Логирование ошибок браузера
Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

// --- РОУТЫ С АВТОРИЗАЦИЕЙ ---
Route::middleware(['auth', 'verified'])->group(function () {

    // ГЛАВНАЯ ПАНЕЛЬ (Dashboard)
    Route::get('/dashboard', function () {
        return view('dashboard', [
            'stats' => [
                'total_users'   => User::count(),
                'online_now'    => Matchmaking::whereIn('status', ['searching', 'matched'])->count(),
                'total_minutes' => User::sum('total_minutes'),
                'active_rooms'  => Room::where('is_public', true)->count(),
            ]
        ]);
    })->name('dashboard');

    // Таблица лидеров
    Route::get('/leaderboard', function () { 
        return view('leaderboard'); 
    })->name('leaderboard');

    // Пульс (Heartbeat) для обновления статуса last_seen
    Route::post('/ping', function () { 
        return response()->json(['status' => 'pong']); 
    })->name('ping');

    // ЖАЛОБЫ И БЛОКИРОВКИ
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    // ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // ВИДЕО-ЧАТ (РУЛЕТКА) И МЕССЕНДЖЕР
    // Здесь мы используем префикс пути 'chat', но НЕ используем ->name('chat.'), чтобы сохранить имена роутов как были
    Route::prefix('chat')->controller(ChatController::class)->group(function () {
        Route::get('/', function () { return view('chat'); })->name('chat'); // Имя роута: chat
        
        // Matchmaking и Сигналы WebRTC
        Route::post('/search', 'startSearching');
        Route::post('/leave', 'leaveChat');
        Route::post('/signal', 'sendSignal');
        
        // Контакты и Друзья
        Route::get('/contacts', 'getContacts');
        Route::post('/contact/add', 'addContact');
        Route::post('/contact/call', 'callContact');
        
        // История и Сообщения
        Route::get('/history-all', 'getInteractionHistory');
        Route::get('/history/{contactId}', 'getChatHistory');
        Route::post('/message/send', 'sendMessage');
        Route::post('/message/typing', 'sendTypingSignal');

        // Черный список
        Route::get('/blocked', 'getBlockedUsers');
        Route::post('/unblock', 'unblockUser');
    });

    // КОМНАТЫ (SPACES)
    Route::controller(RoomController::class)->prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', 'index')->name('index');                // rooms.index
        Route::post('/', 'store')->name('store');              // rooms.store
        Route::get('/{uuid}', 'show')->name('show');           // rooms.show
        Route::post('/{uuid}/join', 'join')->name('join');     // rooms.join
        Route::delete('/{uuid}', 'destroy')->name('destroy');  // rooms.destroy
        
        // Исправленный роут синхронизации:
        Route::post('/{uuid}/sync-occupancy', 'syncOccupancy')->name('sync-occupancy'); // rooms.sync-occupancy
    });
});

require __DIR__.'/auth.php';