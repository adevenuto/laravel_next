<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * DEV-UNLOCK: nullable JSON column that backs the "Mark all complete" /
     * "Restore my progress" toggle on the dashboard. Populated when the user
     * snapshots their real progress before unlocking everything; cleared on
     * restore or full reset. Production builds never write to it (the toggle
     * endpoint is gated to local/testing in routes/api.php).
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('progress_snapshot')->nullable()->after('streak_last_active_date');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('progress_snapshot');
        });
    }
};
