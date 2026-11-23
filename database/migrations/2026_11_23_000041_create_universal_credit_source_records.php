<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universal_credit_manual_entries', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('assessment_period_id');
            $table->string('direction', 16);
            $table->string('event_type', 48);
            $table->string('category', 80);
            $table->date('cash_date');
            $table->decimal('original_amount', 15, 2);
            $table->char('original_currency', 3);
            $table->unsignedBigInteger('amount_gbp_pence');
            $table->text('description');
            $table->text('evidence_missing_reason')->nullable();
            $table->string('status', 16)->default('active');
            $table->foreignId('created_by')->nullable();
            $table->foreignId('updated_by')->nullable();
            $table->foreignId('voided_by')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_manual_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('assessment_period_id', 'uc_manual_period_fk')->references('id')->on('universal_credit_assessment_periods')->restrictOnDelete();
            $table->foreign('created_by', 'uc_manual_created_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by', 'uc_manual_updated_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->foreign('voided_by', 'uc_manual_voided_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->index(['business_id', 'assessment_period_id', 'cash_date'], 'uc_manual_business_period_date_index');
            $table->index(['business_id', 'status', 'event_type'], 'uc_manual_business_status_type_index');
        });

        Schema::create('universal_credit_report_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('assessment_period_id');
            $table->string('source_type', 120);
            $table->unsignedBigInteger('source_id');
            $table->string('source_event_key', 48);
            $table->char('source_identity_hash', 64);
            $table->char('source_fingerprint', 64);
            $table->string('direction', 16);
            $table->string('category', 80);
            $table->string('classification_status', 24)->default('review_required');
            $table->date('cash_date');
            $table->date('document_date')->nullable();
            $table->decimal('original_amount', 15, 2);
            $table->char('original_currency', 3);
            $table->unsignedBigInteger('gross_amount_pence')->nullable();
            $table->bigInteger('vat_amount_pence')->nullable();
            $table->unsignedSmallInteger('business_use_basis_points')->default(10000);
            $table->text('business_use_reason')->nullable();
            $table->text('classification_reason')->nullable();
            $table->string('calculation_method', 40)->default('actual');
            $table->bigInteger('business_share_pence')->nullable();
            $table->bigInteger('reportable_amount_pence')->nullable();
            $table->json('source_context')->nullable();
            $table->boolean('source_is_current')->default(true);
            $table->string('source_change_reason', 80)->nullable();
            $table->foreignId('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_items_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('assessment_period_id', 'uc_items_period_fk')->references('id')->on('universal_credit_assessment_periods')->restrictOnDelete();
            $table->foreign('reviewed_by', 'uc_items_reviewed_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['assessment_period_id', 'source_identity_hash'], 'uc_items_period_source_unique');
            $table->index(['business_id', 'assessment_period_id', 'classification_status'], 'uc_items_business_period_status_index');
            $table->index(['business_id', 'cash_date', 'direction'], 'uc_items_business_date_direction_index');
            $table->index(['source_type', 'source_id'], 'uc_items_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_credit_report_items');
        Schema::dropIfExists('universal_credit_manual_entries');
    }
};
