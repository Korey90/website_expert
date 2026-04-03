<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->text('description')->nullable()->after('title');
            $table->longText('custom_css')->nullable()->after('meta_description');
            $table->json('settings')->nullable()->after('custom_css');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn(['description', 'custom_css', 'settings']);
        });
    }
};