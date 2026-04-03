<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('name');
            $table->string('slug', 100)->unique();
            $table->string('locale', 10)->default('en');
            $table->string('timezone', 50)->default('Europe/London');
            $table->string('logo_path', 500)->nullable();
            $table->string('primary_color', 7)->nullable();
            $table->string('plan', 50)->default('free');
            $table->boolean('is_active')->default(true);
            $table->timestamp('trial_ends_at')->nullable();
            $table->string('stripe_customer_id')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('is_active');
            $table->index('plan');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
