<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_credit_notes', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('number');
            $table->foreignId('expense_id');
            $table->foreignId('supplier_id');
            $table->foreignId('created_by');
            $table->foreignId('issued_by')->nullable();
            $table->foreignId('allocated_by')->nullable();
            $table->foreignId('voided_by')->nullable();
            $table->string('status', 32)->default('draft');
            $table->char('currency', 3)->default('GBP');
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
            $table->decimal('refunded_amount', 15, 2)->default(0);
            $table->timestamp('issued_at')->nullable();
            $table->timestamp('allocated_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->restrictOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->restrictOnDelete();
            $table->foreign('issued_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('allocated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['business_id', 'number']);
            $table->index(['business_id', 'status', 'issue_date']);
            $table->index(['business_id', 'supplier_id']);
        });

        Schema::create('purchase_credit_note_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('purchase_credit_note_id');
            $table->foreignId('expense_item_id');
            $table->foreignId('tax_code_id')->nullable();
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
            $table->foreign('purchase_credit_note_id')->references('id')->on('purchase_credit_notes')->cascadeOnDelete();
            $table->foreign('expense_item_id')->references('id')->on('expense_items')->restrictOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->index(['business_id', 'purchase_credit_note_id'], 'purchase_credit_items_tenant_note_index');
            $table->index(['expense_item_id', 'purchase_credit_note_id'], 'purchase_credit_items_expense_note_index');
        });

        Schema::create('purchase_credit_refunds', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('purchase_credit_note_id');
            $table->foreignId('expense_id');
            $table->foreignId('received_by')->nullable();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->string('method', 50);
            $table->string('reference')->nullable();
            $table->date('received_at');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('purchase_credit_note_id')->references('id')->on('purchase_credit_notes')->restrictOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->restrictOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'received_at']);
            $table->index(['business_id', 'expense_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_credit_refunds');
        Schema::dropIfExists('purchase_credit_note_items');
        Schema::dropIfExists('purchase_credit_notes');
    }
};
