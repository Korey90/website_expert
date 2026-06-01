<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->nullable()->constrained('domains')->nullOnDelete();
            $table->foreignId('domain_order_id')->nullable()->constrained('domain_orders')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            // Event types: registered, renewed, transferred, nameservers_updated,
            //              expiry_reminder_sent, cancelled, failed, whois_updated
            $table->string('type', 60);
            $table->text('description')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'created_at']);
            $table->index(['domain_order_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_events');
    }
};
