<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('search_ranking_checks', function (Blueprint $table) {
            $table->id();

            // Nullable — public/anonymous tool by default. Set when a future SEO project/client owns the check.
            $table->char('business_id', 26)->nullable();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();

            $table->string('keyword', 150);
            $table->string('domain', 255);          // normalized: no protocol/www/path
            $table->string('location', 150);
            $table->string('location_code', 50)->nullable(); // provider-specific location id (e.g. DataForSEO)
            $table->string('device', 20);            // desktop|mobile
            $table->string('language', 10)->default('en');

            $table->string('status', 20);            // found|not_found|failed
            $table->unsignedSmallInteger('position')->nullable();
            $table->string('ranking_url', 2000)->nullable();

            $table->string('provider', 50);           // manual|dataforseo
            $table->boolean('from_cache')->default(false);
            $table->string('ip_hash', 64)->nullable(); // SHA-256(ip) — abuse prevention only, never raw IP
            $table->string('error_message', 500)->nullable(); // diagnostics for 'failed' status, no secrets

            $table->timestamp('created_at')->useCurrent(); // immutable log entry — no updated_at

            $table->index(['keyword', 'domain', 'location', 'device', 'language'], 'src_cache_lookup_idx');
            $table->index('business_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('search_ranking_checks');
    }
};
