<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('universal_credit_report_items', function (Blueprint $table): void {
            $table->decimal('gbp_exchange_rate', 18, 8)->nullable()->after('gross_amount_pence');
            $table->string('exchange_rate_source', 100)->nullable()->after('gbp_exchange_rate');
            $table->date('exchange_rate_date')->nullable()->after('exchange_rate_source');
            $table->text('fx_snapshot_reason')->nullable()->after('exchange_rate_date');
            $table->string('vat_snapshot_source', 32)->nullable()->after('vat_amount_pence');
            $table->text('vat_snapshot_reason')->nullable()->after('vat_snapshot_source');
            $table->string('calculation_rule', 48)->nullable()->after('calculation_method');
        });

        Schema::create('universal_credit_simplified_expenses', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('assessment_period_id');
            $table->foreignId('policy_version_id');
            $table->string('type', 48);
            $table->json('inputs');
            $table->unsignedBigInteger('calculated_amount_pence');
            $table->char('policy_rules_hash', 64);
            $table->text('description')->nullable();
            $table->text('evidence_missing_reason')->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('voided_by')->nullable();
            $table->timestamp('calculated_at');
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_simplified_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('assessment_period_id', 'uc_simplified_period_fk')->references('id')->on('universal_credit_assessment_periods')->restrictOnDelete();
            $table->foreign('policy_version_id', 'uc_simplified_policy_fk')->references('id')->on('universal_credit_policy_versions')->restrictOnDelete();
            $table->foreign('created_by', 'uc_simplified_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by', 'uc_simplified_voided_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'assessment_period_id', 'status'], 'uc_simplified_business_period_status_index');
            $table->index(['business_id', 'type', 'status'], 'uc_simplified_business_type_status_index');
        });

        DB::table('universal_credit_report_items')
            ->whereNotNull('gross_amount_pence')
            ->where('original_amount', '>', 0)
            ->update([
                'gbp_exchange_rate' => DB::raw('ROUND((gross_amount_pence / 100) / original_amount, 8)'),
                'exchange_rate_source' => 'legacy_source_snapshot',
                'exchange_rate_date' => DB::raw('cash_date'),
                'fx_snapshot_reason' => 'Backfilled from the committed GBP source snapshot by migration 000042.',
            ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_credit_simplified_expenses');

        Schema::table('universal_credit_report_items', function (Blueprint $table): void {
            $table->dropColumn([
                'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
                'fx_snapshot_reason', 'vat_snapshot_source', 'vat_snapshot_reason',
                'calculation_rule',
            ]);
        });
    }
};
