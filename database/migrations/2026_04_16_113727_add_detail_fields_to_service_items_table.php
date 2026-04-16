<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            // Translatable rich-text body (long description)
            $table->json('body')->nullable()->after('description');

            // Translatable short badge / eyebrow label
            $table->json('badge_text')->nullable()->after('body');

            // Featured image / mockup
            $table->string('image_path', 500)->nullable()->after('badge_text');

            // Benefits / features list — array of {text_en, text_pl, text_pt}
            $table->json('features')->nullable()->after('image_path');

            // FAQ accordion — array of {q_en, q_pl, q_pt, a_en, a_pl, a_pt}
            $table->json('faq')->nullable()->after('features');

            // Custom CTA per service (translatable label + URL)
            $table->json('cta_label')->nullable()->after('faq');
            $table->string('cta_url', 255)->nullable()->after('cta_label');

            // SEO meta fields
            $table->json('meta_title')->nullable()->after('cta_url');
            $table->json('meta_description')->nullable()->after('meta_title');
        });
    }

    public function down(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            $table->dropColumn([
                'body', 'badge_text', 'image_path',
                'features', 'faq',
                'cta_label', 'cta_url',
                'meta_title', 'meta_description',
            ]);
        });
    }
};
