<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrowserLogController extends Controller
{
    public function store(Request $request)
    {
        // 1. Валидация входящих данных
        $validated = $request->validate([
            'message' => 'required|string',
            'level'   => 'nullable|string',
            'url'     => 'nullable|url',
        ]);

        // 2. Логирование полученных данных в файл storage/logs/laravel.log
        Log::channel('single')->info('Браузерный лог:', [
            'message' => $validated['message'],
            'level'   => $validated['level'] ?? 'info',
            'url'     => $validated['url'] ?? 'unknown',
            'ip'      => $request->ip(),
        ]);

        // 3. Возвращаем успешный ответ
        return response()->json([
            'status' => 'success',
            'message' => 'Лог успешно записан'
        ], 200);
    }
}