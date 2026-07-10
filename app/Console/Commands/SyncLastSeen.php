<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Redis};
use App\Models\User;
use Carbon\Carbon;

class SyncLastSeen extends Command
{
    protected $signature = 'sync:last-seen';
    protected $description = 'Перенос меток активности из Redis в базу данных';

    public function handle()
    {
        // Получаем все данные из Redis
        $data = Redis::hgetall('users_last_seen');
        if (empty($data)) return;

        $this->info('Синхронизация ' . count($data) . ' пользователей...');

        // Группируем по 100 для массового обновления
        $chunks = array_chunk($data, 100, true);

        foreach ($data as $userId => $timestamp) {
            // Используем updateQuietly, чтобы не триггерить лишние события
            \App\Models\User::where('id', (int)$userId)->update([
                // Принудительно устанавливаем время в формате UTC
                'last_seen' => \Carbon\Carbon::createFromTimestamp((int)$timestamp, 'UTC')
            ]);
        }

        // Очищаем обработанные ключи
        Redis::del('users_last_seen');
        $this->info('Успешно синхронизировано.');
    }
}