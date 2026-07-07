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

        if (empty($data)) {
            return;
        }

        $this->info('Синхронизация ' . count($data) . ' пользователей...');

        // Группируем по 100 для массового обновления
        $chunks = array_chunk($data, 100, true);

        foreach ($chunks as $chunk) {
            DB::transaction(function () use ($chunk) {
                foreach ($chunk as $userId => $timestamp) {
                    User::where('id', (int)$userId)->update([
                        'last_seen' => Carbon::createFromTimestamp((int)$timestamp)
                    ]);
                }
            });
        }

        // Очищаем обработанные ключи
        Redis::del('users_last_seen');
        $this->info('Успешно синхронизировано.');
    }
}