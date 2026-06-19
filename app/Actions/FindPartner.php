<?php

namespace App\Actions;

use App\Models\Matchmaking;
use App\Enums\MatchmakingStatus;
use App\Events\MatchFoundEvent;
use Illuminate\Support\Facades\DB;

class FindPartner
{
    /**
     * Логика поиска партнера:
     * 1. Удалить старые записи пользователя.
     * 2. Найти кого-то, кто ждет (Waiting).
     * 3. Если найден — соединить их.
     * 4. Если нет — поставить текущего в очередь (Waiting).
     */
    public function execute(int $userId): ?int
    {
        return DB::transaction(function () use ($userId) {
            // Очищаем очередь от текущего пользователя
            Matchmaking::where('user_id', $userId)->delete();

            // Ищем свободного кандидата (используем lockForUpdate для надежности в high-load)
            $waitingUser = Matchmaking::where('user_id', '!=', $userId)
                ->where('status', MatchmakingStatus::Waiting)
                ->lockForUpdate()
                ->first();

            if ($waitingUser instanceof Matchmaking) {
                $partnerId = $waitingUser->user_id;

                // Обновляем статусы обоих
                $waitingUser->update(['status' => MatchmakingStatus::Matched]);
                Matchmaking::create([
                    'user_id' => $userId, 
                    'status' => MatchmakingStatus::Matched
                ]);

                // Оповещаем сокеты
                broadcast(new MatchFoundEvent(targetUserId: $partnerId, partnerId: $userId));
                broadcast(new MatchFoundEvent(targetUserId: $userId, partnerId: $partnerId));

                return $partnerId;
            }

            // Если никто не найден, встаем в очередь сами
            Matchmaking::create([
                'user_id' => $userId,
                'status' => MatchmakingStatus::Waiting
            ]);

            return null;
        });
    }
}