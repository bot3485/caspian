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

Route::get('/', function () { return view('welcome'); })->name('home');
Route::post('_boost/browser-logs', [BrowserLogController::class, 'store']);

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/leaderboard', function () { return view('leaderboard'); })->name('leaderboard');
    Route::post('/ping', function () {
        if (Auth::check()) {
            Matchmaking::where('user_id', Auth::id())->update(['updated_at' => now()]);
        }
        return response()->json(['status' => 'pong']);
    })->name('ping');

    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    Route::controller(ProfileController::class)->group(function () {
        Route::get('/profile', 'edit')->name('profile.edit');
        Route::patch('/profile', 'update')->name('profile.update');
        Route::delete('/profile', 'destroy')->name('profile.destroy');
    });

    Route::prefix('chat')->group(function () {
            Route::get('/', [ChatController::class, 'index'])->name('chat');

            Route::controller(ChatController::class)->group(function () {
                Route::post('/search', 'startSearching')->name('chat.search');
                Route::post('/leave', 'leaveChat')->name('chat.leave');
                Route::post('/signal', 'sendSignal')->name('chat.signal');
                
                // Использование метода контроллера
                Route::get('/user-info/{user}', 'getUserInfo')->name('chat.user-info');

                Route::get('/contacts', 'getContacts')->name('chat.contacts');
                Route::post('/contact/add', 'addContact')->name('chat.contact.add');
                Route::post('/contact/call', 'callContact')->name('chat.contact.call');
                Route::get('/history-all', 'getInteractionHistory')->name('chat.history.all');
                Route::get('/history/{contactId}', 'getChatHistory')->name('chat.history.single');
                Route::post('/message/send', 'sendMessage')->name('chat.message.send');
                Route::post('/message/typing', 'sendTypingSignal')->name('chat.message.typing');
                Route::get('/blocked', 'getBlockedUsers')->name('chat.blocked');
                Route::post('/unblock', 'unblockUser')->name('chat.unblock');
            });
        });

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