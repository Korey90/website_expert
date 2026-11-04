<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('supplier_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('approved_by')->nullable();
            $table->foreignId('posted_by')->nullable();
            $table->foreignId('voided_by')->nullable();
            $table->string('supplier_invoice_number')->nullable();
            $table->string('status', 24)->default('draft');
            $table->char('currency', 3)->default('GBP');
            $table->decimal('gbp_exchange_rate', 18, 8)->nullable();
            $table->string('exchange_rate_source', 100)->nullable();
            $table->date('exchange_rate_date')->nullable();
            $table->date('issue_date');
            $table->date('tax_point');
            $table->date('due_date')->nullable();
            $table->date('received_date')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('subtotal_gbp', 15, 2)->nullable();
            $table->decimal('vat_amount_gbp', 15, 2)->nullable();
            $table->decimal('total_gbp', 15, 2)->nullable();
            $table->decimal('amount_paid', 15, 2)->default(0);
            $table->decimal('amount_due', 15, 2)->default(0);
            $table->string('payment_method', 50)->nullable();
            $table->text('business_purpose')->nullable();
            $table->text('notes')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(
                ['business_id', 'supplier_id', 'supplier_invoice_number'],
                'expenses_tenant_supplier_invoice_unique',
            );
            $table->index(['business_id', 'status', 'issue_date']);
            $table->index(['business_id', 'due_date']);
        });

        Schema::create('expense_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('expense_id');
            $table->foreignId('tax_code_id')->nullable();
            $table->unsignedBigInteger('ledger_account_id')->nullable();
            $table->foreignId('client_id')->nullable();
            $table->foreignId('project_id')->nullable();
            $table->string('description');
            $table->decimal('quantity', 12, 2)->default(1);
            $table->decimal('unit_price', 15, 2);
            $table->date('supply_date')->nullable();
            $table->decimal('net_amount', 15, 2)->nullable();
            $table->decimal('net_amount_gbp', 15, 2)->nullable();
            $table->decimal('vat_rate_snapshot', 5, 2)->nullable();
            $table->decimal('vat_amount', 15, 2)->nullable();
            $table->decimal('vat_amount_gbp', 15, 2)->nullable();
            $table->decimal('gross_amount', 15, 2)->nullable();
            $table->decimal('gross_amount_gbp', 15, 2)->nullable();
            $table->boolean('billable')->default(false);
            $table->unsignedInteger('order')->default(0);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->cascadeOnDelete();
            $table->foreign('tax_code_id')->references('id')->on('tax_codes')->nullOnDelete();
            $table->foreign('client_id')->references('id')->on('clients')->nullOnDelete();
            $table->foreign('project_id')->references('id')->on('projects')->nullOnDelete();
            $table->index(['business_id', 'expense_id']);
            $table->index(['business_id', 'tax_code_id']);
            $table->index(['business_id', 'ledger_account_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expense_items');
        Schema::dropIfExists('expenses');
    }
};
