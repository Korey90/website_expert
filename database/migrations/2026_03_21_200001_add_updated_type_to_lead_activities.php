<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE `lead_activities` MODIFY COLUMN `type` ENUM(
            'created',
            'stage_moved',
            'marked_won',
            'marked_lost',
            'email_sent',
            'note_updated',
            'project_created',
            'assigned',
            'deleted',
            'restored',
            'updated',
            'other'
        ) NOT NULL");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE `lead_activities` MODIFY COLUMN `type` ENUM(
            'created',
            'stage_moved',
            'marked_won',
            'marked_lost',
            'email_sent',
            'note_updated',
            'project_created',
            'assigned',
            'deleted',
            'restored',
            'other'
        ) NOT NULL");
    }
};
