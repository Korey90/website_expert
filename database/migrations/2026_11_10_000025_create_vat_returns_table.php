<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vat_returns', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('number', 50);
            $table->string('status', 20)->default('draft');
            $table->date('period_from');
            $table->date('period_to');
            $table->unsignedInteger('snapshot_version')->default(1);
            $table->json('snapshot');
            $table->char('snapshot_hash', 64);
            $table->unsignedInteger('source_journal_count')->default(0);
            $table->unsignedInteger('source_line_count')->default(0);
            $table->unsignedInteger('warning_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->string('review_confirmation_version', 50)->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->text('lock_reason')->nullable();
            $table->foreignId('superseded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('superseded_at')->nullable();
            $table->text('supersede_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'number'], 'vat_returns_business_number_unique');
            $table->index(['business_id', 'status', 'period_from', 'period_to'], 'vat_returns_business_status_period_index');
            $table->index(['business_id', 'period_from', 'period_to'], 'vat_returns_business_period_index');
            $table->index(['business_id', 'snapshot_hash'], 'vat_returns_business_hash_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vat_returns');
    }
};
