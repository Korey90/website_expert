<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefings', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26)->index();
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('briefing_template_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('conducted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('client_token', 64)->nullable()->unique();
            $table->timestamp('client_submitted_at')->nullable();
            $table->string('title');
            $table->enum('type', ['discovery', 'qualification', 'proposal_input', 'sales_offer', 'custom'])->default('discovery');
            $table->char('language', 2)->default('en');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->json('answers')->nullable();
            $table->timestamp('autosave_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['lead_id', 'status']);
            $table->index(['business_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefings');
    }
};
