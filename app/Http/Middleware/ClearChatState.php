<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Actions\LeaveChat;
use Illuminate\Support\Facades\Auth;

class ClearChatState
{
    public function __construct(protected LeaveChat $leaveChat) {}

    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            // Очищаем состояние чата/рулетки
            $this->leaveChat->execute(Auth::id());
        }

        return $next($request);
    }
}