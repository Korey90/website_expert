<?php

namespace App\Actions\AI;

use App\Models\AiConversation;
use App\Models\AiLead;
use App\Models\AiMessage;
use App\Models\AiUnansweredQuestion;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;
use Illuminate\Support\Facades\DB;

final class EraseAiConversationAction
{
    /**
     * Erase PII after an administrator reviews a deletion request.
     * Preserve existing CRM leads and their consent audit, if present.
     */
    public function execute(string $conversationId): void
    {
        $business = AiAccess::business('manage_ai_conversations');

        DB::transaction(function () use ($business, $conversationId) {
            $conversation = AiConversation::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->whereKey($conversationId)
                ->lockForUpdate()
                ->firstOrFail();

            $hasLead = AiLead::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->where('conversation_id', $conversation->id)
                ->exists();

            if (! $hasLead) {
                $conversation->delete();

                return;
            }

            AiUnansweredQuestion::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->where('conversation_id', $conversation->id)
                ->delete();

            AiMessage::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->where('conversation_id', $conversation->id)
                ->delete();

            $conversation->forceFill([
                'visitor_hash' => bin2hex(random_bytes(32)),
                'status' => 'archived',
                'last_message_at' => null,
            ])->save();
        });
    }
}
