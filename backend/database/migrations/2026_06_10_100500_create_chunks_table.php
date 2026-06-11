<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chunks', function (Blueprint $table) {
            $table->id();
            $table->string('es');
            $table->string('en');
            $table->string('literal_en')->nullable();
            $table->string('audio_key');
            $table->string('situation_tag')->nullable();
            $table->string('grammar_unlock_key')->nullable();
            $table->foreignId('unit_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->unique('audio_key');
            $table->index('situation_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chunks');
    }
};
