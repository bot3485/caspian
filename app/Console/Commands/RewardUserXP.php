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
    $matches = Matchmaking::where('status', MatchmakingStatus::Matched)->with('user')->get();

    foreach ($matches as $match) {
        $user = $match->user;
        if (!$user || !$user->isOnline()) continue;

        // Коэффициент XP от кармы: 
        // Карма 0-50: x0.5 XP
        // Карма 50-150: x1.0 XP
        // Карма 200+: x1.5 XP (VIP бонус)
        $multiplier = 1.0;
        if ($user->karma < 50) $multiplier = 0.5;
        elseif ($user->karma >= 200) $multiplier = 1.5;

        $xpGained = (int)(15 * $multiplier);
        $user->increment('xp', $xpGained);
        $user->increment('total_minutes', 1);

        // Бонус кармы за долгое общение (каждые 5 минут +1 карма)
        if ($user->total_minutes % 5 === 0 && $user->karma < 1000) {
            $user->increment('karma', 1);
        }

        // Логика нового уровня
        $oldLevel = $user->level;
        $newLevel = floor($user->xp / 1000) + 1;
        
        if ($newLevel > $oldLevel) {
            $user->update(['level' => $newLevel]);
            // Можно отправить специальный ивент LevelUpEvent для салюта на экране
        }

        broadcast(new XpGainedEvent($user->id, $xpGained, $user->xp, $user->level));
    }
}
}