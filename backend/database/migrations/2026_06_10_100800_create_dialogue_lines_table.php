<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dialogue_lines', function (Blueprint $table) {
            $table->id();
            $table->string('audio_key')->unique();
            $table->string('es');
            $table->string('en');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dialogue_lines');
    }
};
