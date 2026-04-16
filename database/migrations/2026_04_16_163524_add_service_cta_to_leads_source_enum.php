<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
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
                    'service_cta',
                    'other'
                ) NOT NULL DEFAULT 'contact_form'
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
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
        }
    }
};
