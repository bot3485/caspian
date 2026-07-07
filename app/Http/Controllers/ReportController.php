<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redis};
use App\Models\User;
use Carbon\Carbon;

class ReportController extends Controller
{
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

        // Проверка: одна жалоба в сутки от одного пользователя на другого
        $alreadyReported = DB::table('reports')
            ->where('reporter_id', $reporterId)
            ->where('reported_id', $reportedId)
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($alreadyReported) {
            return response()->json(['status' => 'already_reported'], 200);
        }

        return DB::transaction(function() use ($reporterId, $reportedId, $validated) {
            DB::table('reports')->insert([
                'reporter_id' => $reporterId,
                'reported_id' => $reportedId,
                'reason' => $validated['reason'],
                'created_at' => now(),
            ]);

            $reportedUser = User::find($reportedId);
            if ($reportedUser) {
                // Срезаем 20 единиц кармы за каждую жалобу
                $reportedUser->decrement('karma', 20);
                
                // Если карма упала слишком низко — бан
                if ($reportedUser->karma <= 0) {
                    $reportedUser->update([
                        'banned_until' => Carbon::now()->addHours(12),
                        'karma' => 50 // Сбрасываем до 50 после бана
                    ]);
                }
            }

            return response()->json(['status' => 'success']);
        });
    }
}