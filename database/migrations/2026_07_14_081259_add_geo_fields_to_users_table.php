<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // ISO 2-символьный код (например: ru, tr, us)
            $table->string('country_code', 2)->default('us')->after('email');
            // Фильтр выбора страны для общения (по умолчанию любая - 'global')
            $table->string('target_country', 6)->default('global')->after('country_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['country_code', 'target_country']);
        });
    }
};