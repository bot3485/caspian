<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\RoomController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\BrowserLogController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\ClearChatState;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/icebreaker/random', [ChatController::class, 'getRandomIcebreakerIndex']);
Route::get('/icebreaker/content/{index}', [ChatController::class, 'getIcebreakerContent']);
Route::post('/chat/contact/accept', [ChatController::class, 'acceptFriend']);
Route::post('/chat/contact/decline', [ChatController::class, 'declineFriend']);
Route::post('/chat/contact/remove', [ChatController::class, 'removeContact']);

Route::get('/', function () { 
    return view('welcome'); 
})->name('home');

Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

Route::post('/lang/set', function (Request $request) {
    $lang = $request->input('locale');
    if (in_array($lang, ['en', 'ru', 'tr'])) {
        session(['locale' => $lang]);
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

    // 1. РОУТЫ С АВТО-ОЧИСТКОЙ СТАТУСА (Middleware ClearChatState)
    // При переходе сюда пользователь становится "свободным" (удаляется из matchmaking_queue)
    Route::middleware([ClearChatState::class])->group(function () {
        
        Route::get('/dashboard', function () {
            return view('dashboard.index');
        })->name('dashboard');

        Route::get('/leaderboard', [ChatController::class, 'leaderboard'])->name('leaderboard');

        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Список всех комнат (Лобби) — здесь мы еще свободны
        Route::get('/rooms', [RoomController::class, 'index'])->name('rooms.index');
    });

    // 2. РОУТЫ БЕЗ АВТО-ОЧИСТКИ (Здесь статус "Matched" должен сохраняться)
    
    // Групповые комнаты (Внутри комнаты)
    Route::controller(RoomController::class)->prefix('rooms')->name('rooms.')->group(function () {
        // middleware ClearChatState тут НЕ НУЖЕН, чтобы не удалять статус Matched, установленный контроллером
        Route::get('/{uuid}', 'show')->name('show'); 
        Route::post('/', 'store')->name('store');
        Route::post('/{uuid}/join', 'join')->name('join');
        Route::delete('/{uuid}', 'destroy')->name('destroy');
        Route::post('/{uuid}/sync-occupancy', 'syncOccupancy')->name('sync-occupancy');
    });

    // Система активности (Ping) - должна работать всегда
    Route::post('/ping', function () {
        if (Auth::check()) {
            \App\Models\Matchmaking::where('user_id', Auth::id())->update(['updated_at' => now()]);
            
            $match = \App\Models\Matchmaking::where('user_id', Auth::id())->first();
            if ($match && $match->partner_id) {
                \Illuminate\Support\Facades\Redis::expire("allow_signal:".Auth::id().":{$match->partner_id}", 3600);
                \Illuminate\Support\Facades\Redis::expire("allow_signal:{$match->partner_id}:".Auth::id(), 3600);
            }
        }
        return response()->json(['status' => 'pong']);
    })->name('ping');

    // Жалобы
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

// Видеочат и Мессенджер
    Route::prefix('chat')->group(function () {
        Route::get('/', [ChatController::class, 'index'])->name('chat');

        Route::controller(ChatController::class)->group(function () {
            Route::post('/search', 'startSearching')->name('chat.search');
            Route::post('/leave', 'leaveChat')->name('chat.leave');
            Route::post('/signal', 'sendSignal')->name('chat.signal');
            Route::get('/user-info/{hashid}', 'getUserInfo')->name('chat.user-info');
            Route::get('/contacts', 'getContacts')->name('chat.contacts');
            Route::post('/contact/add', 'addContact')->name('chat.contact.add');
            Route::post('/contact/call', 'callContact')->name('chat.contact.call');
            Route::post('/message/send', 'sendMessage')->name('chat.message.send');
            Route::post('/message/typing', 'sendTypingSignal')->name('chat.message.typing');
            
            // ДОБАВЬТЕ СТРОКУ НИЖЕ:
            Route::post('/mark-as-read', 'markAsRead')->name('chat.mark-as-read');

            Route::get('/history/{hashid}', 'getChatHistory')->name('chat.history.single');
            Route::get('/history-all', 'getInteractionHistory')->name('chat.history.all');
            Route::get('/blocked', 'getBlockedUsers')->name('chat.blocked');
            Route::post('/block', 'blockUser')->name('chat.block');
            Route::post('/unblock', 'unblockUser')->name('chat.unblock');
            Route::post('/clear-messages', 'clearChat')->name('chat.clear'); // Убрал дублирование массива [ChatController::class]
        });
    });
});

require __DIR__.'/auth.php';