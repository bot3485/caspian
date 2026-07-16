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

    return DB::transaction(function() use ($reporterId, $reportedId, $request) {
        $user = \App\Models\User::lockForUpdate()->find($reportedId);
        if (!$user) return response()->json(['error' => 'Not found'], 404);

        // 1. ШТРАФ ЗА ЖАЛОБУ: -30 кармы сразу
        $user->decrement('karma', 30);
        if ($user->karma < 0) $user->update(['karma' => 0]);

        // 2. ЗАПИСЬ ЖАЛОБЫ (в блоках и репортах)
        DB::table('blocks')->updateOrInsert(
            ['blocker_id' => $reporterId, 'blocked_id' => $reportedId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        DB::table('reports')->insert([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $request->reason ?? 'general',
            'created_at' => now()
        ]);

        // 3. СЧИТАЕМ НОВЫЕ ЖАЛОБЫ (только те, что прилетели после последнего бана)
        // Если банов еще не было, считаем с момента регистрации
        $sinceDate = $user->last_ban_at ?? $user->created_at;
        $activeReportsCount = DB::table('reports')
            ->where('reported_id', $reportedId)
            ->where('created_at', '>', $sinceDate)
            ->count();

        // 4. ЛОГИКА БАНА (если набралось 5 новых жалоб)
        if ($activeReportsCount >= 5) {
            $user->increment('ban_count'); // Увеличиваем общий счетчик банов
            $user->decrement('karma', 100); // Дополнительный штраф -100 за сам бан
            if ($user->karma < 0) $user->update(['karma' => 0]);

            // ОПРЕДЕЛЯЕМ ДЛИТЕЛЬНОСТЬ БАНА (Прогрессия)
            $banDays = match ($user->ban_count) {
                1 => 1,       // 1-й раз: день
                2 => 7,       // 2-й раз: неделя
                3 => 30,      // 3-й раз: месяц
                default => 36500, // 4-й раз и далее: 100 лет (пермач)
            };

            $user->update([
                'banned_until' => now()->addDays($banDays),
                'last_ban_at' => now(), // Фиксируем время, чтобы обнулить отсчет жалоб
            ]);

            // Выкидываем нарушителя из системы
            broadcast(new \App\Events\WebRTCSignalEvent($reportedId, [
                'type' => 'you-are-blocked',
                'reason' => 'System ban level ' . $user->ban_count
            ]));
        }

        // Разрываем текущий матч в любом случае
        broadcast(new \App\Events\WebRTCSignalEvent($reportedId, ['type' => 'peer-disconnected']));
        \App\Models\Matchmaking::whereIn('user_id', [$reporterId, $reportedId])->delete();

        return response()->json(['status' => 'success', 'ban_level' => $user->ban_count]);
    });
}
}