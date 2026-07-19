<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Widens the lead_activities.type column in SQLite (test environment) to string,
 * removing the original CHECK constraint that was never updated for newer types
 * (task_created, event_scheduled, briefing_* etc.) because those migrations skip SQLite.
 *
 * MySQL/MariaDB: no-op — type is already a proper ENUM updated by previous migrations.
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb' || $driver === 'pgsql') {
            return; // Handled by previous individual ENUM ALTER migrations
        }

        // SQLite: change enum column to string to remove the stale CHECK constraint.
        // doctrine/dbal will recreate the table internally — safe for test databases.
        Schema::table('lead_activities', function (Blueprint $table) {
            $table->string('type', 100)->change();
        });
    }

    public function down(): void
    {
        // No rollback needed — the CHECK constraint is not re-added
    }
};
