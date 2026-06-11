<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cognate_words', function (Blueprint $table) {
            $table->id();
            $table->string('en');
            $table->string('es');
            $table->string('rule_key');
            $table->boolean('is_exception')->default(false);
            $table->string('exception_note')->nullable();
            $table->string('audio_key');
            $table->timestamps();

            $table->unique(['rule_key', 'en']);
            $table->unique('audio_key');
            $table->index('rule_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cognate_words');
    }
};
