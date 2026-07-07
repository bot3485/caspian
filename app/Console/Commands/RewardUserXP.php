<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Matchmaking;
use App\Models\User;
use App\Enums\MatchmakingStatus;

class RewardUserXP extends Command
{
    protected $signature = 'app:reward-xp';
    protected $description = 'Начисляет XP пользователям в активных чатах';

    public function handle()
    {
        // Находим всех, кто сейчас находится в состоянии 'matched' (в паре)
        $activeUserIds = Matchmaking::where('status', MatchmakingStatus::Matched)
            ->pluck('user_id');

        if ($activeUserIds->isEmpty()) return;

        foreach ($activeUserIds as $userId) {
            $user = User::find($userId);
            if (!$user) continue;

            // Начисляем 10 XP за каждую минуту общения
            $user->increment('xp', 10);

            // Проверка на повышение уровня (каждые 1000 XP)
            $newLevel = floor($user->xp / 1000) + 1;
            if ($newLevel > $user->level) {
                $user->update(['level' => $newLevel]);
                // Здесь в будущем можно отправлять уведомление о новом уровне
            }
        }
    }
}