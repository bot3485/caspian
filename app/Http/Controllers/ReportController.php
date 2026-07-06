<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redis};

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

        // Защита от спама: проверяем, был ли репорт сегодня
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
        Redis::incr($key);
        Redis::expire($key, 86400); 

        return response()->json(['status' => 'success']);
    }
}