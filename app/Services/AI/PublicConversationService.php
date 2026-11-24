<?php

namespace App\Services\AI;

use App\Models\AiAssistant;
use App\Models\AiConversation;
use App\Models\AiMessage;
use App\Scopes\BusinessScope;
use Illuminate\Http\Request;

final class PublicConversationService
{
    private function sessionKey(string $businessId): string
    {
        return 'public_ai_conversation_'.$businessId;
    }

    private function fingerprint(Request $request): string
    {
        // Only the backend can derive this fingerprint.
        return hash_hmac('sha256', $request->session()->getId(), (string) config('app.key'));
    }

    public function current(Request $request, AiAssistant $assistant): ?AiConversation
    {
        $key = $this->sessionKey($assistant->business_id);
        $id = $request->session()->get($key);
        if (! is_string($id) || $id === '') {
            return null;
        }

        return AiConversation::withoutGlobalScope(BusinessScope::class)
            ->whereKey($id)
            ->where('business_id', $assistant->business_id)
            ->where('assistant_id', $assistant->id)
            ->where('visitor_hash', $this->fingerprint($request))
            ->where('updated_at', '>=', now()->subDays((int) config('ai_assistant.conversation_retention_days')))
            ->where('status', 'active')
            ->first();
    }

    public function getOrStart(Request $request, AiAssistant $assistant, string $locale): AiConversation
    {
        $existing = $this->current($request, $assistant);
        if ($existing) {
            return $existing;
        }

        $conversation = new AiConversation;
        $conversation->forceFill([
            'business_id' => $assistant->business_id,
            'assistant_id' => $assistant->id,
            'visitor_hash' => $this->fingerprint($request),
            'locale' => $locale,
            'status' => 'active',
        ])->save();

        $request->session()->put($this->sessionKey($assistant->business_id), $conversation->id);

        return $conversation;
    }

    public function forget(Request $request, AiAssistant $assistant): void
    {
        $request->session()->forget($this->sessionKey($assistant->business_id));
    }

    /** @return array<int,array{id:string,role:string,text:string,status:?string,sources:array}> */
    public function history(AiConversation $conversation): array
    {
        return AiMessage::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $conversation->business_id)
            ->where('conversation_id', $conversation->id)
            ->whereIn('role', ['user', 'assistant'])
            ->latest('created_at')
            ->limit((int) config('ai_assistant.max_history_messages'))
            ->get()
            ->reverse()
            ->values()
            ->map(fn (AiMessage $message): array => [
                'id' => (string) $message->id,
                'role' => $message->role,
                'text' => $message->content,
                'status' => $message->status,
                'sources' => $message->sources ?? [],
            ])->all();
    }
}
