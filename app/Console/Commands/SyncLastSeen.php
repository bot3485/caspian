<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Redis};
use App\Models\User;

class SyncLastSeen extends Command
{
    protected $signature = 'sync:last-seen';
    protected $description = 'Перенос меток last_seen из Redis в MySQL';

    public function handle()
    {
        // 1. Получаем все данные из хеша
        $data = Redis::hgetall('users_last_seen');

        if (empty($data)) {
            $this->info('Нет данных для синхронизации.');
            return;
        }

        $this->info('Синхронизация ' . count($data) . ' пользователей...');

        // 2. Используем транзакцию для массового обновления
        DB::transaction(function () use ($data) {
            foreach ($data as $userId => $lastSeen) {
                User::where('id', $userId)->update(['last_seen' => $lastSeen]);
            }
        });

        // 3. Очищаем обработанные данные в Redis
        // В идеале можно удалять только те ключи, что мы прочитали, но для начала сойдет и полная очистка
        Redis::del('users_last_seen');

        $this->info('Успешно синхронизировано.');
    }
}