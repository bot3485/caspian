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
        $reportedId = $validated['reported_id'];

        if ($reporterId === $reportedId) {
            return response()->json(['error' => 'Self-report'], 400);
        }

        $alreadyReported = DB::table('reports')
            ->where('reporter_id', $reporterId)
            ->where('reported_id', $reportedId)
            ->where('created_at', '>', now()->subDay())
            ->exists();

        if ($alreadyReported) {
            return response()->json(['status' => 'already_reported'], 200);
        }

        DB::table('reports')->insert([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $validated['reason'],
            'created_at' => now(),
        ]);

        $key = "user_reputation:{$reportedId}";
        $count = Redis::incr($key);
        Redis::expire($key, 86400); 

        // v1.9: Автоматический бан при 5 жалобах
        if ($count >= 5) {
            User::where('id', $reportedId)->update([
                'banned_until' => Carbon::now()->addHours(2)
            ]);
            // Очищаем репутацию после бана, чтобы не банить вечно
            Redis::del($key);
        }

        return response()->json(['status' => 'success']);
    }
}