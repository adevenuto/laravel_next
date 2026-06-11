<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->string('slug')->unique();
            $table->string('title');
            $table->unsignedTinyInteger('order')->default(0);
            $table->string('skill_key');
            $table->json('teach_screens');
            $table->timestamps();

            $table->index('skill_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
