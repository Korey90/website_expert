<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_periods', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('name');
            $table->unsignedSmallInteger('financial_year');
            $table->unsignedSmallInteger('period_number')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->string('status', 20)->default('open');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closing_started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closing_started_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->text('close_notes')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('locked_at')->nullable();
            $table->text('lock_reason')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->text('reopen_reason')->nullable();
            $table->json('transition_history')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'start_date', 'end_date'], 'period_business_dates_unique');
            $table->index(['business_id', 'status', 'start_date', 'end_date'], 'period_business_status_dates_index');
            $table->index(['business_id', 'financial_year', 'period_number'], 'period_business_year_number_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
