<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table): void {
            $table->foreignId('created_by')->nullable()->after('invoice_id')->constrained('users')->nullOnDelete();
            $table->decimal('amount_gbp', 15, 2)->nullable()->after('amount');
            $table->decimal('gbp_exchange_rate', 18, 8)->nullable()->after('currency');
            $table->string('exchange_rate_source', 100)->nullable()->after('gbp_exchange_rate');
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate_source');
            $table->timestamp('refunded_at')->nullable()->after('paid_at');
            $table->index(['business_id', 'status', 'paid_at'], 'payments_tenant_status_paid_index');
        });

        Schema::create('expense_payments', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('expense_id');
            $table->foreignId('created_by')->nullable();
            $table->decimal('amount', 15, 2);
            $table->char('currency', 3);
            $table->decimal('amount_gbp', 15, 2);
            $table->decimal('gbp_exchange_rate', 18, 8);
            $table->string('exchange_rate_source', 100);
            $table->date('exchange_rate_date');
            $table->string('method', 50);
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('paid_at');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('expense_id')->references('id')->on('expenses')->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'paid_at']);
            $table->index(['business_id', 'expense_id']);
            $table->index(['business_id', 'reference']);
        });

        Schema::table('purchase_credit_refunds', function (Blueprint $table): void {
            $table->decimal('amount_gbp', 15, 2)->nullable()->after('amount');
            $table->decimal('gbp_exchange_rate', 18, 8)->nullable()->after('currency');
            $table->string('exchange_rate_source', 100)->nullable()->after('gbp_exchange_rate');
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate_source');
        });

        DB::table('payments')
            ->whereRaw('UPPER(currency) = ?', ['GBP'])
            ->update([
                'amount_gbp' => DB::raw('amount'),
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => DB::raw('DATE(COALESCE(paid_at, created_at))'),
            ]);

        DB::table('purchase_credit_refunds')
            ->whereRaw('UPPER(currency) = ?', ['GBP'])
            ->update([
                'amount_gbp' => DB::raw('amount'),
                'gbp_exchange_rate' => 1,
                'exchange_rate_source' => 'identity',
                'exchange_rate_date' => DB::raw('received_at'),
            ]);
    }

    public function down(): void
    {
        Schema::table('purchase_credit_refunds', function (Blueprint $table): void {
            $table->dropColumn(['amount_gbp', 'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date']);
        });

        Schema::dropIfExists('expense_payments');

        Schema::table('payments', function (Blueprint $table): void {
            $table->dropIndex('payments_tenant_status_paid_index');
            $table->dropConstrainedForeignId('created_by');
            $table->dropColumn([
                'amount_gbp', 'gbp_exchange_rate', 'exchange_rate_source',
                'exchange_rate_date', 'refunded_at',
            ]);
        });
    }
};
