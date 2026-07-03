<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB};

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reported_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string|in:nudity,harassment,spam,other'
        ]);

        $reporterId = Auth::id();
        $reportedId = $validated['reported_id'];

        if ($reporterId == $reportedId) {
            return response()->json(['error' => 'Cannot report yourself'], 400);
        }

        // 1. Записываем жалобу
        DB::table('reports')->insert([
            'reporter_id' => $reporterId,
            'reported_id' => $reportedId,
            'reason' => $validated['reason'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Проверяем количество уникальных жалоб за последние 24 часа
        $reportCount = DB::table('reports')
            ->where('reported_id', $reportedId)
            ->where('created_at', '>=', now()->subDay())
            ->distinct('reporter_id')
            ->count();

        // Если 3 разных человека пожаловались — бан на 24 часа
        if ($reportCount >= 3) {
            User::where('id', $reportedId)->update([
                'banned_until' => now()->addDay()
            ]);
        }

        return response()->json(['status' => 'success']);
    }
}