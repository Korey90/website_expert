<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmrc_vat_submissions', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('vat_return_id')->constrained('vat_returns')->cascadeOnDelete();
            $table->foreignId('hmrc_vat_obligation_id')->constrained('hmrc_vat_obligations')->restrictOnDelete();
            $table->string('environment', 20);
            $table->string('status', 30);
            $table->char('snapshot_hash', 64);
            $table->char('payload_hash', 64);
            $table->string('declaration_version', 50);
            $table->timestamp('declared_at');
            $table->string('mfa_type', 20);
            $table->timestamp('mfa_verified_at');
            $table->char('mfa_reference_hash', 64);
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('processing_date')->nullable();
            $table->text('form_bundle_number')->nullable();
            $table->string('payment_indicator', 20)->nullable();
            $table->text('charge_reference')->nullable();
            $table->string('correlation_id', 100)->nullable();
            $table->string('failure_code', 100)->nullable();
            $table->unsignedSmallInteger('failure_status')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'vat_return_id', 'environment'], 'hmrc_vat_submissions_return_environment_unique');
            $table->index(['business_id', 'status'], 'hmrc_vat_submissions_business_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmrc_vat_submissions');
    }
};
