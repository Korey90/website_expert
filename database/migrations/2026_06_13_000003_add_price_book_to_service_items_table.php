<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            $table->json('price_from_prices')->nullable()->after('price_from');
            $table->string('price_from_period', 20)->nullable()->after('price_from_prices');
        });
    }

    public function down(): void
    {
        Schema::table('service_items', function (Blueprint $table) {
            $table->dropColumn(['price_from_prices', 'price_from_period']);
        });
    }
};
