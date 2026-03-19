<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('site_sections', function (Blueprint $table) {
            $table->dropColumn(['title', 'subtitle', 'body', 'button_text']);
        });

        Schema::table('site_sections', function (Blueprint $table) {
            $table->json('title')->nullable()->after('label');
            $table->json('subtitle')->nullable()->after('title');
            $table->json('body')->nullable()->after('subtitle');
            $table->json('button_text')->nullable()->after('body');
        });
    }

    public function down(): void
    {
        Schema::table('site_sections', function (Blueprint $table) {
            $table->dropColumn(['title', 'subtitle', 'body', 'button_text']);
        });

        Schema::table('site_sections', function (Blueprint $table) {
            $table->string('title')->nullable()->after('label');
            $table->string('subtitle')->nullable()->after('title');
            $table->text('body')->nullable()->after('subtitle');
            $table->string('button_text')->nullable()->after('body');
        });
    }
};
