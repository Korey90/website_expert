<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('service_page_blocks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('service_page_id')
                ->constrained('service_pages')
                ->cascadeOnDelete();

            // hero | features_grid | packages | pricing_table | faq | cta_banner | text_section | comparison_table
            $table->string('type', 50);

            $table->unsignedSmallInteger('sort_order')->default(0);

            // Locale-keyed content, e.g.:
            //   { "heading_en": "...", "heading_pl": "...", "items": [...] }
            $table->json('content')->nullable();

            // Layout/style options, e.g.:
            //   { "bg": "white", "columns": "3", "layout": "full" }
            $table->json('settings')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['service_page_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('service_page_blocks');
    }
};
