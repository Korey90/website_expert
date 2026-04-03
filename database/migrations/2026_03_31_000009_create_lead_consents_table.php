<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
            $table->boolean('given')->default(true);
            $table->text('consent_text');               // full checkbox text — audit trail
            $table->string('consent_version', 20)->nullable()->default('1.0');
            $table->timestamp('collected_at');
            $table->string('source_url', 2000)->nullable();
            $table->string('ip_hash', 64)->nullable();  // SHA-256(ip) — evidence without PII
            $table->string('locale', 10)->nullable()->default('en');
            $table->timestamps();

            $table->unique('lead_id');
            $table->index('given');
            $table->index('collected_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_consents');
    }
};
