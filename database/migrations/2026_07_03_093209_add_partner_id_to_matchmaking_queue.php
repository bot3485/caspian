<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('matchmaking_queue', function (Blueprint $table) {
            // Добавляем ID партнера, чтобы понимать, кто с кем связан
            $table->foreignId('partner_id')->nullable()->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('matchmaking_queue', function (Blueprint $table) {
            $table->dropConstrainedForeignId('partner_id');
        });
    }
};