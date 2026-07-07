<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;
use App\Events\XpGainedEvent;
use Carbon\Carbon;

class RewardUserXP extends Command
{
    protected $signature = 'app:reward-xp';
    protected $description = 'Начисляет XP только активным пользователям в чате';

    public function handle()
    {
        // 1. Ищем записи 'matched', но только для тех пользователей, 
        // кто обновлял страницу (last_seen) не более 2 минут назад.
        $activeUserIds = Matchmaking::where('status', MatchmakingStatus::Matched)
            ->whereHas('user', function($query) {
                $query->where('last_seen', '>=', Carbon::now()->subMinutes(2));
            })
            ->pluck('user_id');

        if ($activeUserIds->isEmpty()) {
            return;
        }

        foreach ($activeUserIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            // Начисляем данные
            $user->increment('xp', 10);
            $user->increment('total_minutes', 1);

            if ($user->karma < 200) {
                $user->increment('karma', 1);
            }

            // Проверка уровня
            $newLevel = floor($user->xp / 1000) + 1;
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            // Оповещаем фронтенд
            broadcast(new XpGainedEvent($user->id, 10, $user->xp, $user->level));
        }
    }
}