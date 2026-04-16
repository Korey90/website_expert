<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('automation_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('automation_rule_id')
                  ->nullable()
                  ->constrained('automation_rules')
                  ->nullOnDelete();
            $table->string('trigger_event', 100);
            $table->json('context')->nullable();
            $table->json('actions_executed')->nullable();
            $table->foreignId('lead_id')->nullable()->constrained('leads')->nullOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->enum('status', ['success', 'partial', 'failed', 'test'])->default('success');
            $table->string('source', 30)->default('automation'); // automation | test
            $table->timestamp('executed_at')->useCurrent();
            $table->timestamps();

            $table->index(['trigger_event', 'executed_at']);
            $table->index(['automation_rule_id', 'executed_at']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('automation_logs');
    }
};
