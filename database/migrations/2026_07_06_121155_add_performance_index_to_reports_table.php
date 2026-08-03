<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Создаем составной индекс для ускорения проверки дубликатов жалоб
            // Порядок полей важен: сначала те, что ищем через '=', потом диапазон (date)
            $table->index(['reporter_id', 'reported_id', 'created_at'], 'reports_spam_check_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Удаляем индекс, если решим откатить миграцию
            $table->dropIndex('reports_spam_check_index');
        });
    }
};