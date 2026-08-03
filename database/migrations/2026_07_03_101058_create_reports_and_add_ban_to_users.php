<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Таблица жалоб
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reporter_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('reported_id')->constrained('users')->onDelete('cascade');
            $table->string('reason'); // 'nudity', 'harassment', 'other'
            $table->timestamps();
        });

        // Поле бана в таблице пользователей
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_until')->nullable();
        });
    }

    public function down(): void {
        Schema::dropIfExists('reports');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('banned_until');
        });
    }
};