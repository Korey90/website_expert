<?php

namespace App\Actions\AI;

use App\Actions\CreateLeadAction;
use App\Models\AiAssistant;
use App\Models\AiConversation;
use App\Models\AiLead;
use App\Models\LeadConsent;
use App\Scopes\BusinessScope;
use App\Services\AI\AiUnavailable;
use App\Services\AI\PublicConversationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

final class CaptureAiLeadAction
{
    public function __construct(
        private readonly PublicConversationService $conversations,
        private readonly CreateLeadAction $createLead,
    ) {}

    /** @return array{success:bool} */
    public function execute(Request $request, AiAssistant $assistant, array $data): array
    {
        if (! $request->boolean('consent')) {
            throw new AiUnavailable('consent_required');
        }

        $conversation = $this->conversations->getOrStart($request, $assistant, 'en');

        return DB::transaction(function () use ($request, $assistant, $conversation, $data) {
            $locked = AiConversation::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $assistant->business_id)
                ->where('assistant_id', $assistant->id)
                ->whereKey($conversation->id)
                ->where('status', 'active')
                ->lockForUpdate()
                ->first();

            if (! $locked) {
                throw new AiUnavailable('conversation_unavailable');
            }

            $exists = AiLead::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $assistant->business_id)
                ->where('conversation_id', $locked->id)
                ->exists();

            if ($exists) {
                return ['success' => true]; // Idempotent.
            }

            // Never include full chat history in CRM. The visitor must explicitly
            // submit the project brief and accept the privacy statement.
            $lead = $this->createLead->execute([
                'name' => trim($data['name']),
                'email' => mb_strtolower(trim($data['email'])),
                'company' => filled($data['company'] ?? null) ? trim($data['company']) : null,
                'project_type' => 'AI Website Enquiry',
                'source' => 'other', // Existing MySQL ENUM; specific channel is source_type.
                'source_type' => 'ai_chat',
                'notes' => trim($data['project']),
                'business_id' => $assistant->business_id,
                'locale' => $locked->locale,
            ]);

            LeadConsent::create([
                'lead_id' => $lead->id,
                'given' => true,
                'consent_text' => __('ai_assistant.lead_consent', [], $locked->locale),
                'consent_version' => 'ai-1.0',
                'collected_at' => now(),
                'source_url' => url('/ai/lead'),
                'ip_hash' => hash_hmac('sha256', (string) $request->ip(), (string) config('app.key')),
                'locale' => $locked->locale,
            ]);

            $link = new AiLead;
            $link->forceFill([
                'business_id' => $assistant->business_id,
                'conversation_id' => $locked->id,
                'lead_id' => $lead->id,
                'consented_at' => now(),
                'source' => 'chat_widget',
            ])->save();

            return ['success' => true];
        });
    }
}
