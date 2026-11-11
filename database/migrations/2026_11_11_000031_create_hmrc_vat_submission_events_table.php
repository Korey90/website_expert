<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmrc_vat_submission_events', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->foreignId('hmrc_vat_submission_id')->constrained('hmrc_vat_submissions')->cascadeOnDelete();
            $table->unsignedInteger('sequence');
            $table->string('event_type', 50);
            $table->string('status', 30);
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('occurred_at');
            $table->json('metadata');
            $table->char('previous_hash', 64)->nullable();
            $table->char('event_hash', 64);
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['hmrc_vat_submission_id', 'sequence'], 'hmrc_vat_submission_events_sequence_unique');
            $table->unique('event_hash', 'hmrc_vat_submission_events_hash_unique');
            $table->index(['business_id', 'occurred_at'], 'hmrc_vat_submission_events_business_time_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmrc_vat_submission_events');
    }
};
