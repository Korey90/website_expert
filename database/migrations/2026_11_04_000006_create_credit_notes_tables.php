<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('number');
            $table->foreignId('invoice_id')->constrained()->restrictOnDelete();
            $table->foreignId('client_id')->constrained()->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('issued_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('allocated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 32)->default('draft');
            $table->string('currency', 3)->default('GBP');
            $table->text('reason');
            $table->date('issue_date');
            $table->date('tax_point');
            $table->decimal('gbp_exchange_rate', 18, 8)->nullable();
            $table->string('exchange_rate_source', 100)->nullable();
            $table->date('exchange_rate_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('subtotal_gbp', 15, 2)->nullable();
            $table->decimal('vat_amount_gbp', 15, 2)->nullable();
            $table->decimal('total_gbp', 15, 2)->nullable();
            $table->decimal('allocated_amount', 15, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'number']);
            $table->index(['business_id', 'status', 'issue_date']);
        });

        Schema::create('credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('credit_note_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invoice_item_id')->constrained()->restrictOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->text('description');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 15, 2);
            $table->date('supply_date')->nullable();
            $table->decimal('net_amount', 15, 2);
            $table->decimal('net_amount_gbp', 15, 2)->nullable();
            $table->decimal('vat_rate_snapshot', 5, 2);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('vat_amount_gbp', 15, 2)->nullable();
            $table->decimal('gross_amount', 15, 2);
            $table->decimal('gross_amount_gbp', 15, 2)->nullable();
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'credit_note_id']);
            $table->index(['invoice_item_id', 'credit_note_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('credit_note_items');
        Schema::dropIfExists('credit_notes');
    }
};
