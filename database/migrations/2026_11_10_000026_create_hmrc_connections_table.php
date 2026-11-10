<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hmrc_connections', function (Blueprint $table): void {
            $table->id();
            $table->char('business_id', 26);
            $table->string('environment', 20);
            $table->json('scopes');
            $table->text('access_token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->text('vrn')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->string('status', 30)->default('connected');
            $table->timestamp('connected_at')->nullable();
            $table->foreignId('connected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('refreshed_at')->nullable();
            $table->foreignId('refreshed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('disconnected_at')->nullable();
            $table->foreignId('disconnected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('last_successful_call_at')->nullable();
            $table->text('reconnect_reason')->nullable();
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->unique(['business_id', 'environment'], 'hmrc_connections_business_environment_unique');
            $table->index(['business_id', 'status'], 'hmrc_connections_business_status_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hmrc_connections');
    }
};
