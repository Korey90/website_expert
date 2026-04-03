<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->string('ai_generation_source', 50)->nullable()->after('ai_generated');
            $table->foreignId('current_generation_id')
                ->nullable()
                ->after('ai_generation_source')
                ->constrained('landing_page_ai_generations')
                ->nullOnDelete();

            $table->index('current_generation_id');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropForeign(['current_generation_id']);
            $table->dropIndex(['current_generation_id']);
            $table->dropColumn(['ai_generation_source', 'current_generation_id']);
        });
    }
};