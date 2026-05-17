<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->nullable()->index();
            $table->foreign('business_id')->references('id')->on('businesses')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('title');
            $table->text('description')->nullable();
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->boolean('all_day')->default(false);

            $table->string('type', 20)->default('meeting'); // meeting|call|deadline|reminder|task
            $table->string('status', 20)->default('scheduled'); // scheduled|done|cancelled
            $table->string('color', 7)->nullable(); // hex e.g. #3b82f6

            // Polymorphic relation to Lead, Project, Contract, Invoice, etc.
            $table->nullableMorphs('related');

            // Google Calendar sync
            $table->string('google_event_id')->nullable()->index();
            $table->timestamp('google_synced_at')->nullable();

            $table->timestamps();

            $table->index(['business_id', 'starts_at']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
