<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domains', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('domain_order_id')->nullable()->constrained('domain_orders')->nullOnDelete();
            $table->string('provider', 50)->nullable();
            $table->string('provider_domain_id')->nullable(); // ID assigned by registrar API
            $table->string('name', 100);        // e.g. "example"
            $table->string('tld', 30);          // e.g. ".co.uk"
            $table->string('full_domain', 130); // e.g. "example.co.uk"
            $table->enum('status', [
                'pending',
                'active',
                'expired',
                'transferred',
                'cancelled',
            ])->default('pending');
            $table->timestamp('registered_at')->nullable();
            $table->date('expires_at')->nullable();
            $table->boolean('auto_renew')->default(false);
            $table->boolean('whois_privacy')->default(true);
            $table->json('nameservers')->nullable();
            $table->json('dns_records')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index(['business_id', 'expires_at']);
            $table->index(['business_id', 'full_domain']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domains');
    }
};
