<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_orders', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('domain_name', 100);   // e.g. "example"
            $table->string('tld', 30);             // e.g. ".co.uk"
            $table->string('full_domain', 130);    // e.g. "example.co.uk"
            $table->unsignedTinyInteger('years')->default(1);
            $table->enum('action', ['register', 'transfer', 'renew'])->default('register');
            $table->enum('status', [
                'pending_payment',
                'paid',
                'registering',
                'completed',
                'failed',
                'cancelled',
            ])->default('pending_payment');
            $table->string('provider', 50)->nullable();
            $table->decimal('wholesale_price', 10, 2)->nullable();
            $table->decimal('retail_price', 10, 2);
            $table->string('currency', 3)->default('GBP');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('auth_code')->nullable(); // for domain transfers
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['business_id', 'status']);
            $table->index('full_domain');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_orders');
    }
};
