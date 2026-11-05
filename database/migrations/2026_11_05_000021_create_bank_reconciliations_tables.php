<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_reconciliations', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 20)->default('active');
            $table->decimal('total_amount', 15, 2);
            $table->char('currency', 3);
            $table->text('notes')->nullable();
            $table->timestamp('reconciled_at');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'bank_transaction_id', 'status'], 'bank_reconciliations_transaction_status_index');
            $table->index(['business_id', 'reconciled_at'], 'bank_reconciliations_date_index');
        });

        Schema::create('bank_reconciliation_allocations', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('bank_reconciliation_id')->constrained('bank_reconciliations')->restrictOnDelete();
            $table->foreignId('bank_transaction_id')->constrained('bank_transactions')->restrictOnDelete();
            $table->string('reconcilable_type', 100);
            $table->unsignedBigInteger('reconcilable_id');
            $table->string('event_type', 40);
            $table->string('candidate_key', 191);
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->decimal('source_amount', 15, 2);
            $table->char('source_currency', 3);
            $table->unsignedTinyInteger('match_score')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'bank_transaction_id'], 'bank_allocations_transaction_index');
            $table->index(
                ['business_id', 'reconcilable_type', 'reconcilable_id', 'event_type'],
                'bank_allocations_source_event_index',
            );
            $table->unique(
                ['bank_reconciliation_id', 'candidate_key'],
                'bank_allocations_batch_candidate_unique',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_reconciliation_allocations');
        Schema::dropIfExists('bank_reconciliations');
    }
};
