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
    Schema::table('users', function (Blueprint $table) {
        // Проверяем каждую колонку перед добавлением
        if (!Schema::hasColumn('users', 'gender')) {
            $table->string('gender')->default('male');
        }
        if (!Schema::hasColumn('users', 'age')) {
            $table->integer('age')->default(18);
        }
        if (!Schema::hasColumn('users', 'target_gender')) {
            $table->string('target_gender')->default('all');
        }
        if (!Schema::hasColumn('users', 'target_age_min')) {
            $table->integer('target_age_min')->default(18);
        }
        if (!Schema::hasColumn('users', 'target_age_max')) {
            $table->integer('target_age_max')->default(99);
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
