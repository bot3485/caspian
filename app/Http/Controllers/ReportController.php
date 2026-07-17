<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Storage};
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $reporterId = Auth::id();
        $reportedId = (int)$request->reported_id;

        return DB::transaction(function() use ($reporterId, $reportedId, $request) {
            $user = \App\Models\User::lockForUpdate()->find($reportedId);
            if (!$user) return response()->json(['error' => 'Not found'], 404);

            // --- ОБРАБОТКА СКРИНШОТА (Evidence) ---
            $evidencePath = null;
            if ($request->filled('image')) {
                try {
                    $imageData = $request->input('image');
                    // Убираем заголовок "data:image/jpeg;base64,"
                    $image = str_replace('data:image/jpeg;base64,', '', $imageData);
                    $image = str_replace(' ', '+', $image);
                    
                    // Генерируем уникальное имя файла
                    $fileName = 'evidence_' . time() . '_' . Str::random(10) . '.jpg';
                    $path = 'reports/' . $fileName;

                    // Сохраняем в папку storage/app/public/reports
                    Storage::disk('public')->put($path, base64_decode($image));
                    $evidencePath = $path;
                } catch (\Exception $e) {
                    \Log::error("Failed to save report screenshot: " . $e->getMessage());
                }
            }

            // 1. ШТРАФ ЗА ЖАЛОБУ: -30 кармы
            $user->decrement('karma', 30);
            if ($user->karma < 0) $user->update(['karma' => 0]);

            // 2. ЗАПИСЬ ЖАЛОБЫ (включая путь к скриншоту)
            DB::table('blocks')->updateOrInsert(
                ['blocker_id' => $reporterId, 'blocked_id' => $reportedId],
                ['created_at' => now(), 'updated_at' => now()]
            );

            DB::table('reports')->insert([
                'reporter_id' => $reporterId,
                'reported_id' => $reportedId,
                'reason' => $request->reason ?? 'general',
                'evidence_path' => $evidencePath, // ТУТ ХРАНИМ ПУТЬ
                'created_at' => now()
            ]);

            // 3. ПРОВЕРКА НА БАН (как у тебя была)
            $sinceDate = $user->last_ban_at ?? $user->created_at;
            $activeReportsCount = DB::table('reports')
                ->where('reported_id', $reportedId)
                ->where('created_at', '>', $sinceDate)
                ->count();

            if ($activeReportsCount >= 5) {
                // Твоя логика бана (1 день, 7 дней и т.д.)
                $user->increment('ban_count');
                $banDays = match ($user->ban_count) {
                    1 => 1, 2 => 7, 3 => 30, default => 36500,
                };

                $user->update([
                    'banned_until' => now()->addDays($banDays),
                    'last_ban_at' => now(),
                ]);

                broadcast(new \App\Events\WebRTCSignalEvent($reportedId, [
                    'type' => 'you-are-blocked',
                    'reason' => 'System ban level ' . $user->ban_count
                ]));
            }

            broadcast(new \App\Events\WebRTCSignalEvent($reportedId, ['type' => 'peer-disconnected']));
            \App\Models\Matchmaking::whereIn('user_id', [$reporterId, $reportedId])->delete();

            return response()->json(['status' => 'success']);
        });
    }
}