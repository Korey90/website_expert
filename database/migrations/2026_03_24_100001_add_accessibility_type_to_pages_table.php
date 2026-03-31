<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return; // SQLite does not enforce enums; existing TEXT column is sufficient
        }

        DB::statement("ALTER TABLE `pages` MODIFY COLUMN `type` ENUM('page', 'policy', 'terms', 'cookie_policy', 'accessibility', 'other') NOT NULL DEFAULT 'page'");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("UPDATE `pages` SET `type` = 'other' WHERE `type` = 'accessibility'");
        DB::statement("ALTER TABLE `pages` MODIFY COLUMN `type` ENUM('page', 'policy', 'terms', 'cookie_policy', 'other') NOT NULL DEFAULT 'page'");
    }
};
