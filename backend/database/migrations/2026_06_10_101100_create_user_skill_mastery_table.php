<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_skill_mastery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('skill_key');
            $table->enum('tier', ['recognize', 'transform', 'produce', 'combine', 'spontaneous'])
                ->default('recognize');
            $table->dateTime('srs_due_at')->nullable();
            $table->unsignedSmallInteger('srs_interval_days')->default(1);
            $table->float('srs_ease')->default(2.5);
            $table->timestamps();

            $table->unique(['user_id', 'skill_key']);
            $table->index('srs_due_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_skill_mastery');
    }
};
