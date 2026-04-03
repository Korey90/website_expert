<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_generation_variants', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();

            $table->foreignId('generation_id')->constrained('landing_page_ai_generations')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            $table->string('title');
            $table->string('slug_suggestion', 100)->nullable();
            $table->string('language', 5)->default('en');
            $table->string('template_key', 50)->nullable();
            $table->json('meta')->nullable();
            $table->json('sections');
            $table->boolean('is_saved')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['generation_id', 'user_id']);
            $table->index(['business_id', 'is_saved']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_generation_variants');
    }
};