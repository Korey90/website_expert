<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('business_profiles', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->unique();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->string('tagline')->nullable();
            $table->text('description')->nullable();
            $table->string('industry', 100)->nullable();
            $table->string('tone_of_voice', 50)->nullable()->default('professional');
            $table->json('target_audience')->nullable();
            $table->json('services')->nullable();
            $table->json('brand_colors')->nullable();
            $table->json('fonts')->nullable();
            $table->string('website_url', 500)->nullable();
            $table->json('social_links')->nullable();
            $table->json('seo_keywords')->nullable();
            $table->text('ai_context_cache')->nullable();
            $table->timestamp('ai_context_updated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('business_profiles');
    }
};
