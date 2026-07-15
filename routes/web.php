<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BrowserLogController;
use Illuminate\Support\Facades\Route;
use App\Models\Matchmaking;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/icebreaker/random', [ChatController::class, 'getRandomIcebreakerIndex']);
Route::get('/icebreaker/content/{index}', [ChatController::class, 'getIcebreakerContent']);
Route::get('/', function () { 
    return view('welcome'); 
})->name('home');

Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);


Route::post('/lang/set', function (Request $request) {
    $lang = $request->input('locale');
    
    // Проверка, что язык поддерживается
    if (in_array($lang, ['en', 'ru', 'tr'])) {
        // Устанавливаем в сессию
        session(['locale' => $lang]);
        
        // Если пользователь авторизован, обновляем и его профиль
        if (auth()->check()) {
            auth()->user()->update(['locale' => $lang]);
        }
        
        return response()->json(['success' => true]);
    }
    
    return response()->json(['error' => 'Invalid language'], 400);
});

/*
|--------------------------------------------------------------------------
| Protected Routes (Auth & Verified)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // --- Основные страницы ---
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/leaderboard', [App\Http\Controllers\ChatController::class, 'leaderboard'])->name('leaderboard');

    // --- Система активности (Ping) ---
    Route::post('/ping', function () {
        if (Auth::check()) {
            // Продлеваем жизнь записи в очереди
            \App\Models\Matchmaking::where('user_id', Auth::id())->update(['updated_at' => now()]);
            
            // Продлеваем жизнь прав на сигналы в Redis (чтобы TURN/сигналы не отвалились через час)
            $match = \App\Models\Matchmaking::where('user_id', Auth::id())->first();
            if ($match && $match->partner_id) {
                \Illuminate\Support\Facades\Redis::expire("allow_signal:".Auth::id().":{$match->partner_id}", 3600);
                \Illuminate\Support\Facades\Redis::expire("allow_signal:{$match->partner_id}:".Auth::id(), 3600);
            }
        }
        return response()->json(['status' => 'pong']);
    })->name('ping');

    // --- Жалобы ---
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    // --- Профиль пользователя ---
    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    // --- Видеочат и Мессенджер ---
    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('chat');

        Route::controller(ChatController::class)->group(function () {
            // Рулетка (Поиск, сигналы и гео-фильтрация)
            Route::post('/search', 'startSearching')->name('chat.search');
            Route::post('/leave', 'leaveChat')->name('chat.leave');
            Route::post('/signal', 'sendSignal')->name('chat.signal');
            Route::get('/user-info/{user}', 'getUserInfo')->name('chat.user-info');

            // Контакты и друзья
            Route::get('/contacts', 'getContacts')->name('chat.contacts');
            Route::post('/contact/add', 'addContact')->name('chat.contact.add');
            Route::post('/contact/call', 'callContact')->name('chat.contact.call');

            // Сообщения и Печать
            Route::post('/message/send', 'sendMessage')->name('chat.message.send');
            Route::post('/message/typing', 'sendTypingSignal')->name('chat.message.typing');
            Route::get('/history/{contactId}', 'getChatHistory')->name('chat.history.single');

            // История взаимодействий (всех встреч)
            Route::get('/history-all', 'getInteractionHistory')->name('chat.history.all');

            // Черный список и блокировка
            Route::get('/blocked', 'getBlockedUsers')->name('chat.blocked');
            Route::post('/block', 'blockUser')->name('chat.block');
            Route::post('/unblock', 'unblockUser')->name('chat.unblock');
        });
    });

    // --- Групповые комнаты (Spaces) ---
    Route::controller(RoomController::class)->prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{uuid}', 'show')->name('show');
        Route::post('/{uuid}/join', 'join')->name('join');
        Route::delete('/{uuid}', 'destroy')->name('destroy');
        Route::post('/{uuid}/sync-occupancy', 'syncOccupancy')->name('sync-occupancy');
    });

});

require __DIR__.'/auth.php';