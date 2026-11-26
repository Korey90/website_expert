<?php

namespace Tests\Feature\AI;

use App\Models\AiAssistant;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Models\Business;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_pruning_deletes_expired_anonymous_conversations(): void
    {
        config()->set('ai_assistant.retention_enabled', true);
        config()->set('ai_assistant.conversation_retention_days', 30);

        $business = Business::create([
            'name' => 'Retention Test', 'slug' => 'retention-test', 'is_active' => true,
        ]);
        $assistant = new AiAssistant;
        $assistant->forceFill([
            'business_id' => $business->id, 'name' => 'Retention AI', 'enabled' => false, 'locale' => 'en',
        ])->save();

        $conversation = new AiConversation;
        $conversation->forceFill([
            'business_id' => $business->id,
            'assistant_id' => $assistant->id,
            'visitor_hash' => hash('sha256', 'expired-session'),
            'locale' => 'en',
            'status' => 'active',
        ])->save();

        $message = new AiMessage;
        $message->forceFill([
            'business_id' => $business->id,
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Some personal chat text',
        ])->save();

        $conversation->timestamps = false;
        $conversation->updated_at = now()->subDays(40);
        $conversation->save();

        $this->artisan('ai:prune-conversations')->assertExitCode(0);

        $this->assertDatabaseMissing('ai_conversations', ['id' => $conversation->id]);
        $this->assertDatabaseMissing('ai_messages', ['id' => $message->id]);
    }
}
