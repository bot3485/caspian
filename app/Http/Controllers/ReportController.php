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
    // ... валидация остается прежней ...
    $reporterId = Auth::id();
    $reportedId = (int)$request->reported_id;

    return DB::transaction(function() use ($reporterId, $reportedId, $request) {
        // 1. Блокируем в БД (чтобы больше не попадались)
        DB::table('blocks')->updateOrInsert([
            'blocker_id' => $reporterId,
            'blocked_id' => $reportedId
        ], ['created_at' => now(), 'updated_at' => now()]);

        // 2. МГНОВЕННЫЙ РАЗРЫВ (Kill-Switch)
        // Отправляем сигнал обоим участникам, чтобы закрыть RTCPeerConnection
        broadcast(new \App\Events\WebRTCSignalEvent($reportedId, [
            'type' => 'peer-disconnected',
            'reason' => 'terminated_by_system',
            'from' => $reporterId
        ]));

        // Чистим права в Redis сразу
        \Illuminate\Support\Facades\Redis::del("allow_signal:{$reporterId}:{$reportedId}");
        \Illuminate\Support\Facades\Redis::del("allow_signal:{$reportedId}:{$reporterId}");

        // 3. Логика штрафа кармы (оставляем твою)
        $reportedUser = \App\Models\User::find($reportedId);
        if ($reportedUser) {
            $reportedUser->decrement('karma', 25); // Увеличим штраф за "Kill-Switch"
            if ($reportedUser->karma <= 0) {
                $reportedUser->update(['banned_until' => now()->addHours(24)]);
            }
        }

        return response()->json(['status' => 'success']);
    });
}
}