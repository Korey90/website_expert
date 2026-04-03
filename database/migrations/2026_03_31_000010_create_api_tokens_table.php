<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('api_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('business_id', 26);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->string('name');                                 // "Zapier Integration"
            $table->string('token_hash', 64)->unique();             // SHA-256 of plain token
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('expires_at')->nullable();            // null = never expires
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['business_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('api_tokens');
    }
};
