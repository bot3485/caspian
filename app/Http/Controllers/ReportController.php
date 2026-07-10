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
        $validated = $request->validate([
            'reported_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|max:255'
        ]);

        $reporterId = Auth::id();
        $reportedId = (int)$validated['reported_id'];

        if ($reporterId === $reportedId) {
            return response()->json(['error' => 'Self-report'], 400);
        }

        return DB::transaction(function() use ($reporterId, $reportedId, $validated) {
            
            // 1. ЗАПИСЫВАЕМ В ЧЕРНЫЙ СПИСОК (Если еще не там)
            DB::table('blocks')->updateOrInsert([
                'blocker_id' => $reporterId,
                'blocked_id' => $reportedId
            ], [
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. ПРОВЕРЯЕМ СПАМ ЖАЛОБАМИ (Не чаще раза в сутки)
            $alreadyReported = DB::table('reports')
                ->where('reporter_id', $reporterId)
                ->where('reported_id', $reportedId)
                ->where('created_at', '>', now()->subDay())
                ->exists();

            if ($alreadyReported) {
                return response()->json(['status' => 'already_reported_but_blocked'], 200);
            }

            // 3. СОЗДАЕМ ЖАЛОБУ
            DB::table('reports')->insert([
                'reporter_id' => $reporterId,
                'reported_id' => $reportedId,
                'reason' => $validated['reason'],
                'created_at' => now(),
            ]);

            // 4. ШТРАФ КАРМЫ И ПРОВЕРКА НА БАН
            $reportedUser = User::find($reportedId);
            if ($reportedUser) {
                // Срезаем 20 единиц кармы
                $reportedUser->decrement('karma', 20);
                
                // Если карма упала в 0 или ниже — системный бан на 12 часов
                if ($reportedUser->karma <= 0) {
                    $reportedUser->update([
                        'banned_until' => Carbon::now()->addHours(12),
                        'karma' => 50 // Частичный сброс после бана
                    ]);
                }
            }

            return response()->json(['status' => 'success', 'info' => 'User blocked and reported']);
        });
    }
}