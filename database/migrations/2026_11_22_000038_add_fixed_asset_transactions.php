<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table): void {
            $table->date('acquisition_date')->nullable()->after('status');
            $table->date('in_service_date')->nullable()->after('acquisition_date');
            $table->decimal('acquisition_cost_gbp', 15, 2)->nullable()->after('in_service_date');
            $table->decimal('accumulated_depreciation_gbp', 15, 2)->default(0)->after('acquisition_cost_gbp');
            $table->date('disposed_at')->nullable()->after('accumulated_depreciation_gbp');
            $table->decimal('disposal_proceeds_gbp', 15, 2)->nullable()->after('disposed_at');
            $table->decimal('disposal_gain_loss_gbp', 15, 2)->nullable()->after('disposal_proceeds_gbp');
            $table->foreignId('activated_by')->nullable()->after('updated_by')->constrained('users')->nullOnDelete();
            $table->timestamp('activated_at')->nullable()->after('activated_by');
            $table->foreignId('disposed_by')->nullable()->after('activated_at')->constrained('users')->nullOnDelete();
            $table->timestamp('disposal_recorded_at')->nullable()->after('disposed_by');

            $table->index(['business_id', 'status', 'in_service_date'], 'fixed_assets_business_status_service_index');
        });

        Schema::create('fixed_asset_transactions', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->restrictOnDelete();
            $table->string('type', 24);
            $table->string('status', 16)->default('prepared');
            $table->date('event_date');
            $table->foreignId('expense_item_id')->nullable()->constrained('expense_items')->restrictOnDelete();
            $table->foreignId('invoice_item_id')->nullable()->constrained('invoice_items')->restrictOnDelete();
            $table->decimal('amount_gbp', 15, 2);
            $table->decimal('cost_basis_gbp', 15, 2)->nullable();
            $table->decimal('accumulated_depreciation_gbp', 15, 2)->nullable();
            $table->decimal('net_book_value_gbp', 15, 2)->nullable();
            $table->decimal('proceeds_gbp', 15, 2)->nullable();
            $table->decimal('gain_loss_gbp', 15, 2)->nullable();
            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('reversal_journal_entry_id')->nullable()->constrained('journal_entries')->restrictOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reversed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'fa_transactions_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique('expense_item_id', 'fa_transactions_expense_item_unique');
            $table->unique('invoice_item_id', 'fa_transactions_invoice_item_unique');
            $table->unique('journal_entry_id', 'fa_transactions_journal_unique');
            $table->unique('reversal_journal_entry_id', 'fa_transactions_reversal_journal_unique');
            $table->index(['business_id', 'fixed_asset_id', 'status', 'event_date'], 'fa_transactions_asset_status_date_index');
            $table->index(['business_id', 'type', 'status'], 'fa_transactions_business_type_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_transactions');

        Schema::table('fixed_assets', function (Blueprint $table): void {
            $table->dropIndex('fixed_assets_business_status_service_index');
            $table->dropConstrainedForeignId('activated_by');
            $table->dropConstrainedForeignId('disposed_by');
            $table->dropColumn([
                'acquisition_date', 'in_service_date', 'acquisition_cost_gbp',
                'accumulated_depreciation_gbp', 'disposed_at', 'disposal_proceeds_gbp',
                'disposal_gain_loss_gbp', 'activated_at', 'disposal_recorded_at',
            ]);
        });
    }
};
