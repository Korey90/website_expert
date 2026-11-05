<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_accounts', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('name');
            $table->string('bank_name');
            $table->char('account_number_last4', 4);
            $table->char('sort_code_last2', 2);
            $table->char('account_fingerprint', 64);
            $table->char('currency', 3)->default('GBP');
            $table->decimal('opening_balance', 15, 2)->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->string('status', 20)->default('active');
            $table->timestamp('last_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('archived_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'account_fingerprint'], 'bank_accounts_business_fingerprint_unique');
            $table->index(['business_id', 'status'], 'bank_accounts_business_status_index');
            $table->index(['business_id', 'currency'], 'bank_accounts_business_currency_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_accounts');
    }
};
