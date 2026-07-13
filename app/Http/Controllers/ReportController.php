<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};
use App\Models\User;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Обработка жалобы с автоматической блокировкой (ЧС).
     */
public function store(Request $request)
{
    $reporterId = Auth::id();
    $reportedId = (int)$request->reported_id;

    return DB::transaction(function() use ($reporterId, $reportedId) {
        // 1. Создаем запись в черном списке
        // Это наш "замок". Пока эта запись существует, юзер не виден в списках.
        DB::table('blocks')->updateOrInsert([
            'blocker_id' => $reporterId,
            'blocked_id' => $reportedId
        ], [
            'created_at' => now(), 
            'updated_at' => now()
        ]);

        // 2. Мгновенно уведомляем нарушителя (Blacklist Protocol)
        // У него на экране выскочит тост и его выкинет в Dashboard
        broadcast(new \App\Events\WebRTCSignalEvent($reportedId, [
            'type' => 'you-are-blocked',
            'from' => $reporterId
        ]));

        // 3. Обрываем текущую активную связь (если она есть)
        // Удаляем обоих из очереди подбора/текущего матча
        \App\Models\Matchmaking::whereIn('user_id', [$reporterId, $reportedId])->delete();

        // 4. Аннулируем ключи доступа к WebRTC сигналам в Redis
        \Illuminate\Support\Facades\Redis::del("allow_signal:{$reporterId}:{$reportedId}");
        \Illuminate\Support\Facades\Redis::del("allow_signal:{$reportedId}:{$reporterId}");

        // 5. Штраф кармы за репорт
        $reportedUser = \App\Models\User::find($reportedId);
        if ($reportedUser) {
            $reportedUser->decrement('karma', 25);
            // Если карма упала в ноль - бан на 24 часа
            if ($reportedUser->karma <= 0) {
                $reportedUser->update(['banned_until' => now()->addHours(24)]);
            }
        }

        // ВАЖНО: Мы НЕ вызываем delete() для таблиц 'contacts' и 'interactions' больше!
        
        return response()->json(['status' => 'success']);
    });
}
}