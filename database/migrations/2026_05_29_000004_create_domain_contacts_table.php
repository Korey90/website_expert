<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_order_id')->constrained('domain_orders')->cascadeOnDelete();
            $table->enum('type', ['registrant', 'admin', 'tech', 'billing'])->default('registrant');
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('email', 191);
            $table->string('phone', 30)->nullable();
            $table->string('organisation', 191)->nullable();
            $table->string('address_line1', 191);
            $table->string('address_line2', 191)->nullable();
            $table->string('city', 100);
            $table->string('county', 100)->nullable();
            $table->string('postcode', 20);
            $table->string('country_code', 2)->default('GB');
            $table->timestamps();

            $table->index(['domain_order_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_contacts');
    }
};
