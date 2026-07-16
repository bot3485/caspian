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
        // 1. Добавляем в ЧС (если уже есть - обновим время)
        DB::table('blocks')->updateOrInsert(
            ['blocker_id' => $reporterId, 'blocked_id' => $reportedId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        DB::table('contacts')
            ->where(fn($q) => $q->where('user_id', $reporterId)->where('contact_id', $reportedId))
            ->orWhere(fn($q) => $q->where('user_id', $reportedId)->where('contact_id', $reporterId))
            ->delete();

        // 2. Логика жалобы (статистика)
        DB::table('reports')->insert([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $request->reason ?? 'general',
            'created_at' => now()
        ]);

        // 3. Уведомляем и обрываем связь
        broadcast(new \App\Events\WebRTCSignalEvent($reportedId, ['type' => 'you-are-blocked']));
        
        // Удаляем из текущей очереди подбора
        \App\Models\Matchmaking::whereIn('user_id', [$reporterId, $reportedId])->delete();

        return response()->json(['status' => 'success']);
    });
}
}