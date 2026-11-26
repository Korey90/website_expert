<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('assistant_id');
            $table->char('visitor_hash', 64);
            $table->string('locale', 10)->default('en');
            $table->string('status', 24)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id'], 'ai_conversations_tenant_key');
            $table->foreign(['business_id', 'assistant_id'], 'ai_conv_assistant_fk')
                ->references(['business_id', 'id'])->on('ai_assistants')->restrictOnDelete();
            $table->index(['business_id', 'visitor_hash'], 'ai_conv_session_lookup');
            $table->index(['business_id', 'updated_at'], 'ai_conv_retention_lookup');
        });

        Schema::create('ai_messages', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('conversation_id');
            $table->uuid('client_message_id')->nullable();
            $table->ulid('in_reply_to_id')->nullable();
            $table->string('role', 16);
            $table->longText('content');
            $table->string('status', 24)->nullable();
            $table->json('sources')->nullable();
            $table->timestamps();
            $table->unique(['business_id', 'id'], 'ai_messages_tenant_key');
            $table->unique(['conversation_id', 'client_message_id'], 'ai_messages_dedupe');
            $table->foreign(['business_id', 'conversation_id'], 'ai_msg_conversation_fk')
                ->references(['business_id', 'id'])->on('ai_conversations')->cascadeOnDelete();
            $table->index(['business_id', 'conversation_id', 'created_at'], 'ai_messages_history_lookup');
        });

        Schema::create('ai_unanswered_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('conversation_id');
            $table->ulid('message_id');
            $table->longText('question');
            $table->string('status', 24)->default('open');
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['business_id', 'message_id'], 'ai_unanswered_unique_message');
            $table->foreign(['business_id', 'conversation_id'], 'ai_unanswered_conversation_fk')
                ->references(['business_id', 'id'])->on('ai_conversations')->cascadeOnDelete();
            $table->foreign(['business_id', 'message_id'], 'ai_unanswered_message_fk')
                ->references(['business_id', 'id'])->on('ai_messages')->cascadeOnDelete();
            $table->index(['business_id', 'status', 'created_at'], 'ai_unanswered_queue_lookup');
        });

        Schema::create('ai_leads', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('business_id')->constrained('businesses')->restrictOnDelete();
            $table->ulid('conversation_id');
            $table->foreignId('lead_id')->constrained('leads')->restrictOnDelete();
            $table->timestamp('consented_at');
            $table->string('source', 24)->default('chat_widget');
            $table->timestamps();
            $table->unique(['business_id', 'conversation_id'], 'ai_leads_unique_conversation');
            $table->foreign(['business_id', 'conversation_id'], 'ai_leads_conversation_fk')
                ->references(['business_id', 'id'])->on('ai_conversations')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_leads');
        Schema::dropIfExists('ai_unanswered_questions');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
    }
};
