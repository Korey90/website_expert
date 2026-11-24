<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_assistants', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->string('name', 150);
            $table->boolean('enabled')->default(false);
            $table->string('locale', 10)->default('en');
            $table->timestamps();
            $table->unique('business_id');
            $table->unique(['business_id', 'id'], 'ai_assistants_tenant_key');
        });

        Schema::create('ai_knowledge_documents', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('assistant_id');
            $table->string('title', 200);
            $table->string('category', 100)->nullable();
            $table->string('locale', 10);
            $table->string('public_source_url', 2048)->nullable();
            // Circular FK avoided for SQLite portability; only locked publication action may set pointer.
            $table->ulid('active_version_id')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id'], 'ai_documents_tenant_key');
            $table->foreign(['business_id', 'assistant_id'], 'ai_documents_assistant_fk')
                ->references(['business_id', 'id'])->on('ai_assistants')->restrictOnDelete();
            $table->index(['business_id', 'assistant_id', 'archived_at'], 'ai_documents_lookup');
        });

        Schema::create('ai_knowledge_versions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('document_id');
            $table->unsignedInteger('version_no');
            $table->longText('content');
            $table->string('checksum', 64);
            $table->string('status', 24)->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('indexed_at')->nullable();
            $table->string('error_code', 64)->nullable();
            $table->timestamps();
            $table->unique(['document_id', 'version_no']);
            $table->unique(['business_id', 'id'], 'ai_versions_tenant_key');
            $table->foreign(['business_id', 'document_id'], 'ai_versions_document_fk')
                ->references(['business_id', 'id'])->on('ai_knowledge_documents')->restrictOnDelete();
        });

        Schema::create('ai_knowledge_chunks', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('version_id');
            $table->unsignedInteger('ordinal');
            $table->text('text');
            $table->string('content_hash', 64);
            $table->json('embedding');
            $table->string('model', 100);
            $table->unsignedInteger('dimensions');
            $table->timestamps();
            $table->unique(['version_id', 'ordinal']);
            $table->foreign(['business_id', 'version_id'], 'ai_chunks_version_fk')
                ->references(['business_id', 'id'])->on('ai_knowledge_versions')->restrictOnDelete();
            $table->index(['business_id', 'model', 'dimensions'], 'ai_chunks_lookup');
        });

        Schema::create('ai_usage_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('assistant_id');
            $table->uuid('request_id')->unique();
            $table->string('operation', 32);
            $table->string('model', 100);
            $table->unsignedInteger('tokens_input')->nullable();
            $table->unsignedInteger('tokens_output')->nullable();
            $table->unsignedBigInteger('reserved_microusd');
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('status', 24);
            $table->string('error_code', 64)->nullable();
            $table->timestamps();
            $table->foreign(['business_id', 'assistant_id'], 'ai_usage_assistant_fk')
                ->references(['business_id', 'id'])->on('ai_assistants')->restrictOnDelete();
            $table->index(['business_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_usage_logs');
        Schema::dropIfExists('ai_knowledge_chunks');
        Schema::dropIfExists('ai_knowledge_versions');
        Schema::dropIfExists('ai_knowledge_documents');
        Schema::dropIfExists('ai_assistants');
    }
};
