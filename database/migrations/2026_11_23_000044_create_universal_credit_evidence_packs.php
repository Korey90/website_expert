<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('universal_credit_evidence_packs', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('assessment_period_id');
            $table->unsignedSmallInteger('version');
            $table->string('status', 16)->default('pending');
            $table->char('snapshot_hash', 64);
            $table->string('locale', 5)->default('en');
            $table->string('disk', 64)->nullable();
            $table->string('path', 500)->nullable();
            $table->string('filename', 200)->nullable();
            $table->char('zip_sha256', 64)->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->unsignedInteger('included_count')->default(0);
            $table->unsignedInteger('excluded_count')->default(0);
            $table->unsignedSmallInteger('attempt_count')->default(0);
            $table->string('last_error_code', 80)->nullable();
            $table->foreignId('requested_by')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->timestamp('file_deleted_at')->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_packs_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('assessment_period_id', 'uc_packs_period_fk')->references('id')->on('universal_credit_assessment_periods')->restrictOnDelete();
            $table->foreign('requested_by', 'uc_packs_requested_by_fk')->references('id')->on('users')->nullOnDelete();
            $table->unique(['assessment_period_id', 'version'], 'uc_packs_period_version_unique');
            $table->index(['business_id', 'assessment_period_id', 'status'], 'uc_packs_business_period_status_index');
            $table->index(['status', 'expires_at'], 'uc_packs_status_expiry_index');
        });

        Schema::create('universal_credit_evidence_pack_items', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('evidence_pack_id');
            $table->string('logical_path', 500);
            $table->string('item_type', 32);
            $table->string('source_type', 120)->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('attachment_id')->nullable();
            $table->string('inclusion_status', 16);
            $table->string('reason', 80)->nullable();
            $table->unsignedBigInteger('bytes')->nullable();
            $table->char('sha256', 64)->nullable();
            $table->timestamps();

            $table->foreign('business_id', 'uc_pack_items_business_fk')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('evidence_pack_id', 'uc_pack_items_pack_fk')->references('id')->on('universal_credit_evidence_packs')->cascadeOnDelete();
            $table->foreign('attachment_id', 'uc_pack_items_attachment_fk')->references('id')->on('financial_attachments')->nullOnDelete();
            $table->index(['business_id', 'evidence_pack_id', 'inclusion_status'], 'uc_pack_items_business_pack_status_index');
            $table->index(['source_type', 'source_id'], 'uc_pack_items_source_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('universal_credit_evidence_pack_items');
        Schema::dropIfExists('universal_credit_evidence_packs');
    }
};
