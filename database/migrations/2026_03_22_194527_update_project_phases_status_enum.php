<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Step 1: widen ENUM to accept both old and new values simultaneously
        DB::statement("ALTER TABLE project_phases MODIFY COLUMN status ENUM('pending','active','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");

        // Step 2: migrate existing 'active' rows to 'in_progress'
        DB::table('project_phases')->where('status', 'active')->update(['status' => 'in_progress']);

        // Step 3: remove obsolete 'active' value
        DB::statement("ALTER TABLE project_phases MODIFY COLUMN status ENUM('pending','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE project_phases MODIFY COLUMN status ENUM('pending','active','in_progress','completed','cancelled') NOT NULL DEFAULT 'pending'");

        DB::table('project_phases')->where('status', 'in_progress')->update(['status' => 'active']);
        DB::table('project_phases')->where('status', 'cancelled')->update(['status' => 'pending']);

        DB::statement("ALTER TABLE project_phases MODIFY COLUMN status ENUM('pending','active','completed') NOT NULL DEFAULT 'pending'");
    }
};
