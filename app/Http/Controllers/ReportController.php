<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, DB, Redis};

class ReportController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'reported_id' => 'required|integer|exists:users,id',
            'reason' => 'required|string'
        ]);

        $reportedId = $validated['reported_id'];

        // Пишем в БД для админки
        DB::table('reports')->insert([
            'reporter_id' => Auth::id(),
            'reported_id' => $reportedId,
            'reason' => $validated['reason'],
            'created_at' => now(),
        ]);

        // Инкрементируем счетчик в Redis на 24 часа
        $key = "user_reputation:{$reportedId}";
        Redis::incr($key);
        Redis::expire($key, 86400); 

        return response()->json(['status' => 'success']);
    }
}