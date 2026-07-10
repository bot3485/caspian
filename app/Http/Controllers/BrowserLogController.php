<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BrowserLogController extends Controller
{
    public function store(Request $request)
    {
        // Убираем все правила 'required', чтобы запрос никогда не падал с 422
        $data = $request->all();

        Log::channel('single')->info('BrowserLog:', [
            'message' => $data['message'] ?? 'no message',
            'level'   => $data['level'] ?? 'info',
            'url'     => $data['url'] ?? $request->header('referer'),
            'ip'      => $request->ip(),
            'user_id' => auth()->id(),
            'agent'   => $request->header('User-Agent'),
        ]);

        return response()->json(['status' => 'ok']);
    }
}