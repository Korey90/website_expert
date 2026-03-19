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
        Schema::create('calculator_pricing', function (Blueprint $table) {
            $table->id();
            $table->string('category'); // project_type, cms, pages_addon, hosting, extra
            $table->string('key')->unique();
            $table->string('label'); // display name translatable via JSON
            $table->text('description')->nullable();
            $table->decimal('base_cost', 10, 2)->default(0);
            $table->decimal('monthly_cost', 10, 2)->default(0); // for recurring items
            $table->string('cost_formula')->nullable(); // e.g. 'pages > 5 ? (pages-5)*80 : 0'
            $table->string('currency', 3)->default('GBP');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('calculator_pricing');
    }
};
