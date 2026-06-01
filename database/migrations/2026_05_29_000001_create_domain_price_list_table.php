<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_price_list', function (Blueprint $table) {
            $table->id();
            $table->string('tld', 30)->unique(); // .co.uk, .com, .uk, .net, .org
            $table->decimal('register_price', 10, 2);
            $table->decimal('renew_price', 10, 2);
            $table->decimal('transfer_price', 10, 2)->nullable();
            $table->string('currency', 3)->default('GBP');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_price_list');
    }
};
