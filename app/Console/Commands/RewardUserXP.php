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
    $matches = Matchmaking::where('status', MatchmakingStatus::Matched)->get();

    foreach ($matches as $match) {
        $user = $match->user;
        if (!$user || !$user->isOnline()) continue;

        // Обновляем метку времени матча, чтобы cleanup его не удалил
        $match->touch(); 

        $user->increment('xp', 15);
            $user->increment('total_minutes', 1); // Время в рулетке

            // 2. Начисление кармы (Бонус за адекватность)
            // Каждые 10 минут общения без жалоб дают +1 карму (до лимита 500)
            if ($user->total_minutes % 10 === 0 && $user->karma < 500) {
                $user->increment('karma', 1);
            }

            // Проверка уровня
            $newLevel = floor($user->xp / 1000) + 1;
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
            }

            broadcast(new XpGainedEvent($user->id, 15, $user->xp, $user->level));
        }
    }
}