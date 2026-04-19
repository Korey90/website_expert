<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_offer_templates', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->nullable()->index();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            $table->string('service_slug', 100)->nullable()->index();
            $table->char('language', 2)->default('en');
            $table->string('title');
            $table->text('description')->nullable();
            $table->longText('body')->nullable();
            $table->boolean('is_active')->default(true);
            $table->smallInteger('sort_order')->unsigned()->default(0);
            $table->timestamps();

            $table->index(['service_slug', 'language', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_offer_templates');
    }
};
