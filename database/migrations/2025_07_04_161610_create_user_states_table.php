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
        Schema::create('user_states', function (Blueprint $table) {
            $table->bigInteger('user_id')->primary();
            $table->string('state', 50);
            $table->string('temp_name')->nullable();
            $table->string('temp_username')->nullable();
            $table->bigInteger('temp_birthday_chat_id')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('telegram_users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_states');
    }
};
