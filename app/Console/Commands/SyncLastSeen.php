<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Redis};
use App\Models\User;
use Carbon\Carbon;

class SyncLastSeen extends Command
{
    protected $signature = 'sync:last-seen';
    protected $description = 'Оптимизированный перенос меток last_seen из Redis в MySQL';

    public function handle()
    {
        $data = Redis::hgetall('users_last_seen');

        if (empty($data)) {
            return;
        }

        $this->info('Синхронизация ' . count($data) . ' пользователей...');

        $updateData = [];
        foreach ($data as $userId => $timestamp) {
            $updateData[] = [
                'id' => (int)$userId,
                'last_seen' => Carbon::createFromTimestamp((int)$timestamp)->toDateTimeString(),
                'name' => DB::raw('name'), // Необходимые поля для upsert, если они не nullable
                'email' => DB::raw('email'),
                'password' => DB::raw('password'),
            ];
        }

        // Массовое обновление через UPSERT (эффективнее транзакции с циклом)
        // Обновит только поле last_seen, если ID совпадает
        User::upsert($updateData, ['id'], ['last_seen']);

        Redis::del('users_last_seen');
        $this->info('Успешно синхронизировано.');
    }
}