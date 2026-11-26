<?php

namespace App\Console\Commands;

use App\Models\AiConversation;
use App\Models\AiLead;
use App\Models\AiMessage;
use App\Models\AiUnansweredQuestion;
use App\Scopes\BusinessScope;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class PruneAiConversations extends Command
{
    protected $signature = 'ai:prune-conversations {--dry-run : Only count eligible conversations}';

    protected $description = 'Prune expired anonymous AI sessions while preserving consent audit for CRM leads';

    public function handle(): int
    {
        if (! config('ai_assistant.retention_enabled')) {
            $this->warn('AI conversation retention is disabled. Set AI_ASSISTANT_RETENTION_ENABLED=true.');

            return self::SUCCESS;
        }

        $days = max(1, min(365, (int) config('ai_assistant.conversation_retention_days')));
        $cutoff = now()->subDays($days);
        $query = AiConversation::withoutGlobalScope(BusinessScope::class)
            ->where('status', 'active')
            ->where('updated_at', '<', $cutoff);

        if ($this->option('dry-run')) {
            $this->info('Eligible conversations: '.(clone $query)->count());

            return self::SUCCESS;
        }

        $deleted = $archived = 0;

        // All operations are scoped by the stored business_id. No currentBusiness() in CLI.
        $query->select(['id', 'business_id'])->orderBy('id')
            ->chunkById(100, function ($conversations) use ($cutoff, &$deleted, &$archived) {
                foreach ($conversations as $candidate) {
                    DB::transaction(function () use ($candidate, $cutoff, &$deleted, &$archived) {
                        $conversation = AiConversation::withoutGlobalScope(BusinessScope::class)
                            ->where('business_id', $candidate->business_id)
                            ->whereKey($candidate->id)
                            ->where('status', 'active')
                            ->where('updated_at', '<', $cutoff)
                            ->lockForUpdate()
                            ->first();

                        if (! $conversation) {
                            return;
                        }

                        $hasLead = AiLead::withoutGlobalScope(BusinessScope::class)
                            ->where('business_id', $conversation->business_id)
                            ->where('conversation_id', $conversation->id)
                            ->exists();

                        if (! $hasLead) {
                            // Composite FKs cascade messages, unanswered questions and links.
                            $conversation->delete();
                            $deleted++;

                            return;
                        }

                        // Retain only the CRM consent linkage. Remove the conversation
                        // transcript and unanswered text, and erase the visitor fingerprint.
                        AiUnansweredQuestion::withoutGlobalScope(BusinessScope::class)
                            ->where('business_id', $conversation->business_id)
                            ->where('conversation_id', $conversation->id)->delete();

                        AiMessage::withoutGlobalScope(BusinessScope::class)
                            ->where('business_id', $conversation->business_id)
                            ->where('conversation_id', $conversation->id)->delete();

                        $conversation->forceFill([
                            'visitor_hash' => bin2hex(random_bytes(32)),
                            'status' => 'archived',
                            'last_message_at' => null,
                        ])->save();
                        $archived++;
                    });
                }
            });

        $this->info("AI retention complete: {$deleted} deleted; {$archived} archived without transcript.");

        return self::SUCCESS;
    }
}
