<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();          // 'free', 'basic', 'pro', 'agency'
            $table->string('name');                    // 'Free', 'Basic', 'Pro', 'Agency'
            $table->text('description')->nullable();
            $table->unsignedInteger('price_monthly')->default(0);  // w groszach/centach (GBP pence)
            $table->unsignedInteger('price_yearly')->default(0);
            $table->string('stripe_price_id_monthly')->nullable();
            $table->string('stripe_price_id_yearly')->nullable();
            $table->unsignedInteger('max_landing_pages')->nullable();  // null = unlimited
            $table->unsignedInteger('max_ai_per_month')->nullable();   // null = unlimited
            $table->boolean('multi_user')->default(false);
            $table->boolean('custom_domain')->default(false);
            $table->boolean('ab_testing')->default(false);
            $table->json('features')->nullable();      // dodatkowe cechy do wyświetlenia w UI
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
