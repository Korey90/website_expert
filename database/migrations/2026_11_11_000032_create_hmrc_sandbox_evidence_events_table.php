<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmrc_sandbox_evidence_events', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('environment', 20);
            $table->unsignedInteger('sequence');
            $table->string('operation', 40);
            $table->string('outcome', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->string('correlation_id', 100)->nullable();
            $table->json('summary');
            $table->char('previous_hash', 64)->nullable();
            $table->char('event_hash', 64);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(
                ['business_id', 'environment', 'sequence'],
                'hmrc_sandbox_evidence_business_environment_sequence_unique',
            );
            $table->unique('event_hash', 'hmrc_sandbox_evidence_event_hash_unique');
            $table->index(
                ['business_id', 'environment', 'occurred_at'],
                'hmrc_sandbox_evidence_business_environment_time_index',
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmrc_sandbox_evidence_events');
    }
};
