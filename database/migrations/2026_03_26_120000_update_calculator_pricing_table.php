<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            // Fix unique constraint: key alone is not unique, (category, key) is
            $table->dropUnique('calculator_pricing_key_unique');
            $table->unique(['category', 'key'], 'calculator_pricing_category_key_unique');

            // New columns
            $table->string('icon', 20)->nullable()->after('key');
            $table->string('label_pl', 255)->nullable()->after('label');
            $table->text('desc_pl')->nullable()->after('description');
            $table->decimal('multiplier', 5, 3)->default(1.000)->after('monthly_cost');
        });
    }

    public function down(): void
    {
        Schema::table('calculator_pricing', function (Blueprint $table) {
            $table->dropUnique('calculator_pricing_category_key_unique');
            $table->unique('key', 'calculator_pricing_key_unique');

            $table->dropColumn(['icon', 'label_pl', 'desc_pl', 'multiplier']);
        });
    }
};
