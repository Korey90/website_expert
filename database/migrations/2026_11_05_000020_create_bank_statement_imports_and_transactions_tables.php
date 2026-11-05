<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_statement_imports', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->string('original_filename');
            $table->char('file_hash', 64);
            $table->string('status', 30);
            $table->unsignedInteger('total_rows')->default(0);
            $table->unsignedInteger('valid_rows')->default(0);
            $table->unsignedInteger('imported_rows')->default(0);
            $table->unsignedInteger('duplicate_rows')->default(0);
            $table->unsignedInteger('failed_rows')->default(0);
            $table->json('column_mapping');
            $table->json('options')->nullable();
            $table->foreignId('imported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->index(['business_id', 'bank_account_id', 'created_at'], 'bank_imports_account_created_index');
            $table->index(['business_id', 'file_hash'], 'bank_imports_file_hash_index');
        });

        Schema::create('bank_transactions', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('bank_account_id')->constrained('bank_accounts')->restrictOnDelete();
            $table->foreignId('bank_statement_import_id')->nullable()->constrained('bank_statement_imports')->nullOnDelete();
            $table->string('provider_id', 191)->nullable();
            $table->date('booking_date');
            $table->date('value_date')->nullable();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->string('reference', 500)->nullable();
            $table->string('counterparty')->nullable();
            $table->char('source_hash', 64);
            $table->unsignedInteger('import_row_number')->nullable();
            $table->string('reconciliation_status', 30)->default('unmatched');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'bank_account_id', 'provider_id'], 'bank_transactions_provider_unique');
            $table->unique(['business_id', 'bank_account_id', 'source_hash'], 'bank_transactions_source_hash_unique');
            $table->index(['business_id', 'bank_account_id', 'booking_date'], 'bank_transactions_account_date_index');
            $table->index(['business_id', 'reconciliation_status'], 'bank_transactions_reconciliation_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_transactions');
        Schema::dropIfExists('bank_statement_imports');
    }
};
