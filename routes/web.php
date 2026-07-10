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
use Illuminate\Support\Facades\Auth;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/

Route::get('/', function () { 
    return view('welcome'); 
})->name('home');

// Логирование клиентских ошибок (Boost Tools)
Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

/*
|--------------------------------------------------------------------------
| Authenticated & Verified Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->group(function () {

    // --- СЕРВИСНЫЕ РОУТЫ ---

    // ГЛАВНАЯ ПАНЕЛЬ (Dashboard) с передачей статистики
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

    // ТАБЛИЦА ЛИДЕРОВ
    Route::get('/leaderboard', function () { 
        return view('leaderboard'); 
    })->name('leaderboard');

    // ПУЛЬС (Heartbeat) - обновление времени активности для очистки "призраков"
    Route::post('/ping', function () {
        if (Auth::check()) {
            Matchmaking::where('user_id', Auth::id())->update([
                'updated_at' => now()
            ]);
        }
        return response()->json(['status' => 'pong', 'timestamp' => now()->timestamp]);
    })->name('ping');

    // ЖАЛОБЫ И МГНОВЕННЫЙ БАН (Kill-Switch)
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');


    // --- ПРОФИЛЬ ПОЛЬЗОВАТЕЛЯ ---

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });


    // --- ВИДЕО-ЧАТ (РУЛЕТКА) И МЕССЕНДЖЕР ---

    Route::prefix('chat')->group(function () {
        // ИЗМЕНЕНО: Направляем на метод index для очистки старых сессий при входе
        Route::get('/', [ChatController::class, 'index'])->name('chat');

        Route::controller(ChatController::class)->group(function () {
            // Matchmaking и Сигналы
            Route::post('/search', 'startSearching')->name('chat.search');
            Route::post('/leave', 'leaveChat')->name('chat.leave');
            Route::post('/signal', 'sendSignal')->name('chat.signal');
            
            // Быстрое получение данных партнера
            Route::get('/user-info/{user}', function (User $user) {
                return response()->json([
                    'id' => $user->id,
                    'name' => $user->name,
                    'level' => $user->level,
                    'rank_name' => $user->rank_name,
                    'status' => $user->status_data, 
                ]);
            })->name('chat.user-info');

            // Контакты и Друзья
            Route::get('/contacts', 'getContacts')->name('chat.contacts');
            Route::post('/contact/add', 'addContact')->name('chat.contact.add');
            Route::post('/contact/call', 'callContact')->name('chat.contact.call');
            
            // История и Мессенджер
            Route::get('/history-all', 'getInteractionHistory')->name('chat.history.all');
            Route::get('/history/{contactId}', 'getChatHistory')->name('chat.history.single');
            Route::post('/message/send', 'sendMessage')->name('chat.message.send');
            Route::post('/message/typing', 'sendTypingSignal')->name('chat.message.typing');

            // Черный список
            Route::get('/blocked', 'getBlockedUsers')->name('chat.blocked');
            Route::post('/unblock', 'unblockUser')->name('chat.unblock');
        });
    });


    // --- ГРУППОВЫЕ КОМНАТЫ (SPACES) ---

    Route::controller(RoomController::class)->prefix('rooms')->name('rooms.')->group(function () {
        Route::get('/', 'index')->name('index');                // Список комнат
        Route::post('/', 'store')->name('store');              // Создание
        Route::get('/{uuid}', 'show')->name('show');           // Вход в комнату
        Route::post('/{uuid}/join', 'join')->name('join');     // Проверка пароля
        Route::delete('/{uuid}', 'destroy')->name('destroy');  // Удаление (автором)
        
        // Синхронизация онлайна (с проверкой прав доступа)
        Route::post('/{uuid}/sync-occupancy', 'syncOccupancy')->name('sync-occupancy');
    });
});

// Роуты аутентификации (Breeze/Fortify)
require __DIR__.'/auth.php';