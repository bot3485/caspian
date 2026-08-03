<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Используем нативные команды PostgreSQL для максимальной надежности
        
        // 1. Ускоряем поиск по фильтрам в рулетке (Users)
        DB::statement('CREATE INDEX IF NOT EXISTS idx_matchmaking_filters ON users (gender, age, karma)');

        // 2. Индекс на email для быстрого логина (если вдруг его нет)
        DB::statement('CREATE INDEX IF NOT EXISTS users_email_index ON users (email)');

        // 3. Индекс на партнера в очереди (Matchmaking)
        DB::statement('CREATE INDEX IF NOT EXISTS matchmaking_queue_partner_id_index ON matchmaking_queue (partner_id)');

        // 4. Ускоряем выборку истории чата (Messages)
        // Этот индекс критически важен, когда сообщений станет > 100 000
        DB::statement('CREATE INDEX IF NOT EXISTS idx_messages_history ON messages (sender_id, receiver_id, created_at)');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS idx_matchmaking_filters');
        DB::statement('DROP INDEX IF EXISTS matchmaking_queue_partner_id_index');
        DB::statement('DROP INDEX IF EXISTS idx_messages_history');
    }
};