<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('number', 50);
            $table->string('status', 20)->default('draft');
            $table->string('adjustment_type', 30);
            $table->string('vat_side', 10);
            $table->string('direction', 10);
            $table->string('correction_method', 30);
            $table->foreignId('tax_code_id')->constrained('tax_codes')->restrictOnDelete();
            $table->foreignId('control_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('contra_ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->date('effective_date');
            $table->date('discovered_on')->nullable();
            $table->date('original_period_from')->nullable();
            $table->date('original_period_to')->nullable();
            $table->decimal('amount_gbp', 15, 2);
            $table->boolean('is_deliberate')->default(false);
            $table->text('reason');
            $table->string('hmrc_reference')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'number'], 'vat_adjustments_business_number_unique');
            $table->index(['business_id', 'status', 'effective_date'], 'vat_adjustments_business_status_date_index');
            $table->index(['business_id', 'adjustment_type'], 'vat_adjustments_business_type_index');
            $table->index(['business_id', 'correction_method'], 'vat_adjustments_business_method_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_adjustments');
    }
};
