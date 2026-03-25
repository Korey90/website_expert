<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->boolean('notify_email_transactional')->default(true)->after('notes');
            $table->boolean('notify_email_projects')->default(true)->after('notify_email_transactional');
            $table->boolean('notify_email_marketing')->default(true)->after('notify_email_projects');
            $table->boolean('notify_sms')->default(true)->after('notify_email_marketing');
            $table->timestamp('communication_prefs_updated_at')->nullable()->after('notify_sms');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'notify_email_transactional',
                'notify_email_projects',
                'notify_email_marketing',
                'notify_sms',
                'communication_prefs_updated_at',
            ]);
        });
    }
};
