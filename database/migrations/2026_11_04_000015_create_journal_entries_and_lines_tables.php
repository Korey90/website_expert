<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('accounting_period_id')->constrained('accounting_periods')->restrictOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->date('entry_date');
            $table->string('entry_number', 50)->nullable();
            $table->nullableMorphs('source');
            $table->string('status', 20)->default('draft');
            $table->char('currency', 3)->default('GBP');
            $table->string('description');
            $table->string('idempotency_key', 150);
            $table->char('source_snapshot_hash', 64)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'idempotency_key'], 'journal_business_idempotency_unique');
            $table->unique(['business_id', 'entry_number'], 'journal_business_number_unique');
            $table->index(['business_id', 'entry_date', 'status'], 'journal_business_date_status_index');
            $table->index(['business_id', 'accounting_period_id'], 'journal_business_period_index');
        });

        Schema::create('journal_lines', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('tax_code_id')->nullable()->constrained('tax_codes')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->foreignId('project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->unsignedSmallInteger('line_number');
            $table->decimal('debit_gbp', 15, 2)->default(0);
            $table->decimal('credit_gbp', 15, 2)->default(0);
            $table->char('original_currency', 3)->default('GBP');
            $table->decimal('original_amount', 15, 2)->nullable();
            $table->decimal('exchange_rate', 18, 8)->nullable();
            $table->string('memo')->nullable();
            $table->json('dimensions')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['journal_entry_id', 'line_number'], 'journal_line_number_unique');
            $table->index(['business_id', 'ledger_account_id'], 'journal_line_business_account_index');
            $table->index(['business_id', 'tax_code_id'], 'journal_line_business_tax_index');
        });

        $this->addSingleSideAmountConstraint();
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_lines');
        Schema::dropIfExists('journal_entries');
    }

    private function addSingleSideAmountConstraint(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER journal_lines_amount_insert
                BEFORE INSERT ON journal_lines
                WHEN NOT (
                    (NEW.debit_gbp > 0 AND NEW.credit_gbp = 0)
                    OR (NEW.credit_gbp > 0 AND NEW.debit_gbp = 0)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'journal line must contain exactly one positive debit or credit');
                END
                SQL);
            DB::unprepared(<<<'SQL'
                CREATE TRIGGER journal_lines_amount_update
                BEFORE UPDATE OF debit_gbp, credit_gbp ON journal_lines
                WHEN NOT (
                    (NEW.debit_gbp > 0 AND NEW.credit_gbp = 0)
                    OR (NEW.credit_gbp > 0 AND NEW.debit_gbp = 0)
                )
                BEGIN
                    SELECT RAISE(ABORT, 'journal line must contain exactly one positive debit or credit');
                END
                SQL);

            return;
        }

        DB::statement(<<<'SQL'
            ALTER TABLE journal_lines
            ADD CONSTRAINT journal_lines_single_side_amount_check
            CHECK (
                (debit_gbp > 0 AND credit_gbp = 0)
                OR (credit_gbp > 0 AND debit_gbp = 0)
            )
            SQL);
    }
};
