<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('collectible_type', ['badge', 'rebel_word', 'chunk']);
            $table->string('collectible_key');
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();

            $table->unique(['user_id', 'collectible_type', 'collectible_key']);
            $table->index(['user_id', 'collectible_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_collections');
    }
};
