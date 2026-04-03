<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_sources', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();

            $table->string('type', 50);                         // landing_page|contact_form|calculator|api|manual|import|referral

            $table->unsignedBigInteger('landing_page_id')->nullable();
            $table->foreign('landing_page_id')->references('id')->on('landing_pages')->nullOnDelete();

            // UTM parameters
            $table->string('utm_source')->nullable();
            $table->string('utm_medium')->nullable();
            $table->string('utm_campaign')->nullable();
            $table->string('utm_content')->nullable();
            $table->string('utm_term')->nullable();

            // Navigation context
            $table->string('referrer_url', 2000)->nullable();
            $table->string('page_url', 2000)->nullable();

            // Device / network (GDPR: ip_address removed after 30 days)
            $table->string('ip_address', 45)->nullable();
            $table->string('ip_hash', 64)->nullable();          // SHA-256(ip) — permanent
            $table->text('user_agent')->nullable();
            $table->string('device_type', 20)->nullable();      // mobile|tablet|desktop
            $table->char('country_code', 2)->nullable();

            $table->timestamp('created_at')->useCurrent();      // immutable — no updated_at

            // Indexes
            $table->unique('lead_id');
            $table->index('type');
            $table->index(['business_id', 'type']);
            $table->index(['business_id', 'created_at']);
            $table->index('landing_page_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_sources');
    }
};
