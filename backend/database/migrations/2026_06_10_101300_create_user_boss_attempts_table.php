<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_boss_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('boss_scenario_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('stars')->default(0);
            $table->unsignedTinyInteger('hearts_remaining')->default(0);
            $table->json('transcript');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'boss_scenario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_boss_attempts');
    }
};
