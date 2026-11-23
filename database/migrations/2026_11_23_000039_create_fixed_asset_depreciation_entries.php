<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_asset_categories', function (Blueprint $table): void {
            $table->decimal('default_depreciation_rate_percent', 5, 2)->nullable()->after('default_residual_value_percent');
        });

        Schema::table('fixed_assets', function (Blueprint $table): void {
            $table->decimal('depreciation_rate_percent', 5, 2)->nullable()->after('residual_value_percent');
        });

        Schema::create('fixed_asset_depreciation_entries', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('fixed_asset_id');
            $table->date('period_start');
            $table->date('period_end');
            $table->date('posting_date');
            $table->string('status', 16)->default('planned');
            $table->string('method_snapshot', 32);
            $table->decimal('rate_percent_snapshot', 5, 2)->nullable();
            $table->unsignedSmallInteger('useful_life_months_snapshot')->nullable();
            $table->decimal('residual_value_gbp', 15, 2);
            $table->decimal('opening_cost_gbp', 15, 2);
            $table->decimal('opening_accumulated_depreciation_gbp', 15, 2);
            $table->decimal('opening_net_book_value_gbp', 15, 2);
            $table->decimal('amount_gbp', 15, 2);
            $table->decimal('closing_accumulated_depreciation_gbp', 15, 2);
            $table->decimal('closing_net_book_value_gbp', 15, 2);
            $table->foreignId('journal_entry_id')->nullable();
            $table->foreignId('reversal_journal_entry_id')->nullable();
            $table->foreignId('created_by')->nullable();
            $table->foreignId('posted_by')->nullable();
            $table->foreignId('reversed_by')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'fa_depreciation_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('fixed_asset_id', 'fa_depreciation_asset_fk')->references('id')->on('fixed_assets')->restrictOnDelete();
            $table->foreign('journal_entry_id', 'fa_depreciation_journal_fk')->references('id')->on('journal_entries')->restrictOnDelete();
            $table->foreign('reversal_journal_entry_id', 'fa_depreciation_reversal_journal_fk')->references('id')->on('journal_entries')->restrictOnDelete();
            $table->foreign('created_by', 'fa_depreciation_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('posted_by', 'fa_depreciation_posted_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('reversed_by', 'fa_depreciation_reversed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique('journal_entry_id', 'fa_depreciation_journal_unique');
            $table->unique('reversal_journal_entry_id', 'fa_depreciation_reversal_journal_unique');
            $table->index(['business_id', 'fixed_asset_id', 'status', 'period_end'], 'fa_depreciation_asset_status_period_index');
            $table->index(['business_id', 'posting_date', 'status'], 'fa_depreciation_posting_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciation_entries');

        Schema::table('fixed_assets', function (Blueprint $table): void {
            $table->dropColumn('depreciation_rate_percent');
        });

        Schema::table('fixed_asset_categories', function (Blueprint $table): void {
            $table->dropColumn('default_depreciation_rate_percent');
        });
    }
};
