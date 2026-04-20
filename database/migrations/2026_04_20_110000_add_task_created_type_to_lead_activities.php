<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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
            'sms_sent',
            'lp_captured',
            'notification_sent',
            'offer_created',
            'offer_sent',
            'offer_viewed',
            'offer_converted',
            'offer_cta_clicked',
            'task_created',
            'other'
        ) NOT NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

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
            'sms_sent',
            'lp_captured',
            'notification_sent',
            'offer_created',
            'offer_sent',
            'offer_viewed',
            'offer_converted',
            'offer_cta_clicked',
            'other'
        ) NOT NULL");
    }
};
