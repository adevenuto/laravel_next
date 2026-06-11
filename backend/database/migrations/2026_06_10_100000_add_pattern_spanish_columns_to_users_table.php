<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('display_name')->nullable()->after('last_name');
            $table->string('hometown')->nullable()->after('display_name');
            $table->unsignedInteger('xp')->default(0)->after('hometown');
            $table->unsignedInteger('vocab_counter')->default(0)->after('xp');
            $table->unsignedInteger('streak_count')->default(0)->after('vocab_counter');
            $table->date('streak_last_active_date')->nullable()->after('streak_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'display_name',
                'hometown',
                'xp',
                'vocab_counter',
                'streak_count',
                'streak_last_active_date',
            ]);
        });
    }
};
