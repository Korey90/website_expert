<?php

namespace App\Filament\Pages;

use App\Actions\AI\EraseAiConversationAction;
use App\Models\AiConversation;
use App\Models\AiLead;
use App\Models\AiMessage;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;

class AiConversationsPage extends BasePage
{
    protected string $view = 'filament.pages.ai-conversations';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 27;

    public ?string $selectedConversationId = null;

    public static function getNavigationLabel(): string
    {
        return __('ai_assistant.conversations');
    }

    public function mount(): void
    {
        AiAccess::business('view_ai_conversations');
    }

    public function conversations(): array
    {
        $business = AiAccess::business('view_ai_conversations');

        return AiConversation::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)
            ->orderByDesc('last_message_at')
            ->limit(100)
            ->get(['id', 'locale', 'status', 'last_message_at'])
            ->map(function (AiConversation $conversation) use ($business): array {
                $lead = AiLead::withoutGlobalScope(BusinessScope::class)
                    ->where('business_id', $business->id)->where('conversation_id', $conversation->id)
                    ->value('lead_id');

                return [
                    'id' => $conversation->id,
                    'locale' => $conversation->locale,
                    'status' => $conversation->status,
                    'last_message_at' => $conversation->last_message_at?->format('Y-m-d H:i'),
                    'lead_id' => $lead,
                ];
            })->all();
    }

    public function selectConversation(string $conversationId): void
    {
        $business = AiAccess::business('view_ai_conversations');

        AiConversation::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->whereKey($conversationId)
            ->firstOrFail();

        $this->selectedConversationId = $conversationId;
    }

    public function erase(EraseAiConversationAction $action): void
    {
        AiAccess::business('manage_ai_conversations');

        if (! $this->selectedConversationId) {
            return;
        }

        $action->execute($this->selectedConversationId);
        $this->selectedConversationId = null;
    }

    public function selectedMessages(): array
    {
        if (! $this->selectedConversationId) {
            return [];
        }

        $business = AiAccess::business('view_ai_conversations');
        $exists = AiConversation::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->whereKey($this->selectedConversationId)
            ->exists();

        if (! $exists) {
            return [];
        }

        return AiMessage::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)
            ->where('conversation_id', $this->selectedConversationId)
            ->orderBy('created_at')->limit(60)
            ->get(['id','role','content','status','created_at'])
            ->map(fn (AiMessage $message): array => [
                'role' => $message->role,
                'text' => $message->content,
                'status' => $message->status,
                'created_at' => $message->created_at?->format('Y-m-d H:i'),
            ])->all();
    }
}
