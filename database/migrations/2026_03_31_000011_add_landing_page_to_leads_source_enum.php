<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL-only: MODIFY COLUMN to extend ENUM; SQLite uses plain text and is compatible by default
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("
                ALTER TABLE leads
                MODIFY COLUMN source ENUM(
                    'calculator',
                    'contact_form',
                    'referral',
                    'cold_outreach',
                    'social_media',
                    'landing_page',
                    'other'
                ) NOT NULL DEFAULT 'contact_form'
            ");

            DB::statement("ALTER TABLE leads ADD INDEX idx_leads_source (source)");
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE leads DROP INDEX idx_leads_source");

            DB::statement("
                ALTER TABLE leads
                MODIFY COLUMN source ENUM(
                    'calculator',
                    'contact_form',
                    'referral',
                    'cold_outreach',
                    'social_media',
                    'other'
                ) NOT NULL DEFAULT 'contact_form'
            ");
        }
    }
};
