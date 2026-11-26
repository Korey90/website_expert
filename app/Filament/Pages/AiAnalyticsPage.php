<?php

namespace App\Filament\Pages;

use App\Models\AiConversation;
use App\Models\AiLead;
use App\Models\AiMessage;
use App\Models\AiUnansweredQuestion;
use App\Models\AiUsageLog;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;

class AiAnalyticsPage extends BasePage
{
    protected string $view = 'filament.pages.ai-analytics';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 29;

    public int $days = 30;

    public static function getNavigationLabel(): string
    {
        return __('ai_assistant.analytics');
    }

    public function mount(): void
    {
        AiAccess::business('view_ai_analytics');
    }

    public function metrics(): array
    {
        $business = AiAccess::business('view_ai_analytics');
        $since = now()->subDays(max(1, min(90, (int) $this->days)));

        $conversation = AiConversation::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('created_at', '>=', $since);

        $message = AiMessage::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('created_at', '>=', $since)
            ->where('role', 'assistant');

        $unanswered = AiUnansweredQuestion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('created_at', '>=', $since);

        $leads = AiLead::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('created_at', '>=', $since);

        $usage = AiUsageLog::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('created_at', '>=', $since);

        return [
            'conversations' => (clone $conversation)->count(),
            'answered' => (clone $message)->where('status', 'grounded')->count(),
            'unknown' => (clone $message)->where('status', 'unknown')->count(),
            'unavailable' => (clone $message)->where('status', 'unavailable')->count(),
            'unreviewed' => (clone $unanswered)->where('status', 'open')->count(),
            'leads' => (clone $leads)->count(),
            'provider_calls' => (clone $usage)->count(),
            // Worst-case reserved ceiling, not billed cost.
            'reserved_usd' => number_format((float) (clone $usage)->sum('reserved_microusd') / 1000000, 4),
        ];
    }
}
