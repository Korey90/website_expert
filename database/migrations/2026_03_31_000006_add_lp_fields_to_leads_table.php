<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // LP tracking — nullable because lead may come from sources other than LP
            $table->foreignId('landing_page_id')
                ->nullable()
                ->after('calculator_data')
                ->constrained()
                ->nullOnDelete();

            // UTM tracking columns — stored for analytics in v1.1
            $table->string('utm_source', 255)->nullable()->after('landing_page_id');
            $table->string('utm_medium', 255)->nullable()->after('utm_source');
            $table->string('utm_campaign', 255)->nullable()->after('utm_medium');
            $table->string('utm_content', 255)->nullable()->after('utm_campaign');
            $table->string('utm_term', 255)->nullable()->after('utm_content');

            $table->index('landing_page_id');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropForeign(['landing_page_id']);
            $table->dropIndex(['landing_page_id']);
            $table->dropColumn([
                'landing_page_id',
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
            ]);
        });
    }
};
