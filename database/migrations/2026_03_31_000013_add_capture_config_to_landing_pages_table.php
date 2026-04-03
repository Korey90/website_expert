<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            // Default assignee for leads captured from this LP; NULL = unassigned (goes to inbox)
            $table->foreignId('default_assignee_id')
                ->nullable()
                ->after('business_id')
                ->constrained('users')
                ->nullOnDelete();

            // Optional thank-you redirect; NULL = in-page success state
            $table->string('thank_you_url', 2048)->nullable()->after('conversion_goal');

            // JSON array of custom form field definitions [{name, label, type, required, ...}]
            // NULL = use default fields (first_name, email, phone, message, consent)
            $table->json('capture_fields')->nullable()->after('thank_you_url');

            $table->index('default_assignee_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['default_assignee_id']);
            $table->dropIndex(['default_assignee_id']);
            $table->dropColumn(['default_assignee_id', 'thank_you_url', 'capture_fields']);
        });
    }
};
