<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lp_generation_rate_limits', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedInteger('count')->default(0);
            $table->timestamps();

            $table->unique(['business_id', 'year', 'month'], 'rl_business_year_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lp_generation_rate_limits');
    }
};
