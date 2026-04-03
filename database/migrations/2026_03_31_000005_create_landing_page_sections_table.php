<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50);  // hero|features|testimonials|cta|form|faq|text|video
            $table->unsignedSmallInteger('order')->default(0);
            $table->json('content')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_visible')->default(true);
            $table->timestamps();

            $table->index('landing_page_id');
            $table->index(['landing_page_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_sections');
    }
};
