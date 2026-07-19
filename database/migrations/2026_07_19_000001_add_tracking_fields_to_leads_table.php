<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // Google Ads click identifiers
            $table->string('gclid', 255)->nullable()->after('utm_term');
            $table->string('gbraid', 255)->nullable()->after('gclid');
            $table->string('wbraid', 255)->nullable()->after('gbraid');

            // Structural source classification
            $table->string('source_type', 50)->nullable()->after('wbraid');   // 'landing_page', 'contact_form', 'calculator'

            // Device and locale context
            $table->string('device_type', 20)->nullable()->after('source_type');  // 'mobile', 'tablet', 'desktop'
            $table->string('locale', 10)->nullable()->after('device_type');        // 'pl', 'en', 'pt'

            // Audience segment — for CRM filtering and reporting
            $table->string('audience', 100)->nullable()->after('locale');         // 'polish_business_uk', 'ni_business', etc.

            // Static LP slug (no FK — for hardcoded LPs that are not CMS-managed)
            $table->string('landing_page_slug', 255)->nullable()->after('audience');

            $table->index('source_type');
            $table->index('audience');
            $table->index('locale');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['source_type']);
            $table->dropIndex(['audience']);
            $table->dropIndex(['locale']);
            $table->dropColumn([
                'gclid', 'gbraid', 'wbraid',
                'source_type', 'device_type', 'locale',
                'audience', 'landing_page_slug',
            ]);
        });
    }
};
