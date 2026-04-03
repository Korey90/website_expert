<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();

            $table->string('title');
            $table->string('slug', 100);
            $table->string('status', 20)->default('draft');  // draft|published|archived
            $table->string('template_key', 50)->nullable();
            $table->string('language', 5)->default('en');

            $table->string('meta_title', 160)->nullable();
            $table->string('meta_description', 320)->nullable();
            $table->string('og_image_path', 500)->nullable();
            $table->string('conversion_goal', 50)->nullable();  // book_call|download|purchase|contact

            $table->unsignedInteger('views_count')->default(0);
            $table->unsignedInteger('conversions_count')->default(0);
            $table->boolean('ai_generated')->default(false);
            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Composite unique: slug is unique per business tenant
            $table->unique(['business_id', 'slug']);
            $table->index('status');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
