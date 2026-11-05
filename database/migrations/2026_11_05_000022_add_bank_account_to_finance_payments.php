<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('invoice_id')
                ->constrained('bank_accounts')
                ->restrictOnDelete();
        });

        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->foreignId('bank_account_id')
                ->nullable()
                ->after('expense_id')
                ->constrained('bank_accounts')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('expense_payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bank_account_id');
        });

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('bank_account_id');
        });
    }
};
