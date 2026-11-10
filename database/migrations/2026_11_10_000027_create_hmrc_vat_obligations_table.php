<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmrc_vat_obligations', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('environment', 20);
            $table->string('period_key', 18);
            $table->date('start_date');
            $table->date('end_date');
            $table->date('due_date');
            $table->string('status', 20);
            $table->date('received_date')->nullable();
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->timestamp('status_changed_at')->nullable();
            $table->foreignId('last_synced_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('last_correlation_id', 100)->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(
                ['business_id', 'environment', 'period_key'],
                'hmrc_vat_obligations_business_env_period_unique',
            );
            $table->index(
                ['business_id', 'environment', 'status', 'start_date'],
                'hmrc_vat_obligations_business_status_start_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmrc_vat_obligations');
    }
};
