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
        // Данные пользователя
        $table->string('gender')->default('unspecified'); // male, female, other
        $table->integer('age')->nullable();
        
        // Предпочтения для поиска
        $table->string('target_gender')->default('all'); // male, female, all
        $table->integer('target_age_min')->default(18);
        $table->integer('target_age_max')->default(99);
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
