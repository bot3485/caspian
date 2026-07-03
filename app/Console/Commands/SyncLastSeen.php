<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Redis};
use App\Models\User;
use Carbon\Carbon;

class SyncLastSeen extends Command
{
    protected $signature = 'sync:last-seen';
    protected $description = 'Перенос меток last_seen из Redis в MySQL';

    public function handle()
    {
        // 1. Получаем данные из Redis
        // Laravel сам подставит префикс "laravel-database-", если он настроен
        $data = Redis::hgetall('users_last_seen');

        if (empty($data)) {
            $this->info('Нет данных для синхронизации.');
            return;
        }

        $this->info('Синхронизация ' . count($data) . ' пользователей...');

        // 2. Выполняем массовое обновление в одной транзакции
        DB::transaction(function () use ($data) {
            foreach ($data as $userId => $timestamp) {
                // Важно: переводим Timestamp (число) в строку формата MySQL (Y-m-d H:i:s)
                $formattedDate = Carbon::createFromTimestamp((int)$timestamp)->toDateTimeString();

                User::where('id', $userId)->update([
                    'last_seen' => $formattedDate
                ]);
            }
        });

        // 3. Удаляем ключ из Redis, так как данные теперь в MySQL
        Redis::del('users_last_seen');

        $this->info('Успешно синхронизировано.');
    }
}