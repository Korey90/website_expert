<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('domain_renewals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('domain_id')->constrained('domains')->cascadeOnDelete();
            $table->date('due_date');
            $table->unsignedTinyInteger('years')->default(1);
            $table->enum('status', [
                'pending',
                'processing',
                'completed',
                'failed',
                'cancelled',
            ])->default('pending');
            $table->decimal('retail_price', 10, 2)->nullable();
            $table->string('stripe_payment_intent_id')->nullable();
            // Reminder tracking flags
            $table->boolean('notified_30d')->default(false);
            $table->boolean('notified_14d')->default(false);
            $table->boolean('notified_7d')->default(false);
            $table->boolean('notified_1d')->default(false);
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index(['domain_id', 'due_date']);
            $table->index(['status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('domain_renewals');
    }
};
