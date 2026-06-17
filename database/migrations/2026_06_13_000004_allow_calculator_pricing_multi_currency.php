<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            $table->dropUnique('calculator_pricing_category_key_unique');
            $table->unique(['category', 'key', 'currency'], 'calculator_pricing_category_key_currency_unique');
        });
    }

    public function down(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            $table->dropUnique('calculator_pricing_category_key_currency_unique');
            $table->unique(['category', 'key'], 'calculator_pricing_category_key_unique');
        });
    }
};
