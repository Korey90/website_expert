<?php

namespace App\Filament\Pages;

use App\Actions\AI\ReviewUnansweredQuestionAction;
use App\Models\AiUnansweredQuestion;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;
use Filament\Notifications\Notification;

class AiLearningPage extends BasePage
{
    protected string $view = 'filament.pages.ai-learning';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-academic-cap';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 28;

    public string $statusFilter = 'open';

    public static function getNavigationLabel(): string
    {
        return __('ai_assistant.learning');
    }

    public function mount(): void
    {
        AiAccess::business('review_ai_questions');
    }

    public function questions(): array
    {
        $business = AiAccess::business('review_ai_questions');
        $query = AiUnansweredQuestion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id);

        if (in_array($this->statusFilter, ['open', 'reviewed', 'resolved'], true)) {
            $query->where('status', $this->statusFilter);
        }

        return $query->latest()->limit(100)
            ->get(['id','question','status','created_at'])
            ->map(fn (AiUnansweredQuestion $question): array => [
                'id' => $question->id,
                'question' => $question->question,
                'status' => $question->status,
                'created_at' => $question->created_at?->format('Y-m-d H:i'),
            ])->all();
    }

    public function mark(string $id, string $status, ReviewUnansweredQuestionAction $action): void
    {
        AiAccess::business('review_ai_questions');
        $action->execute($id, $status);
        Notification::make()->title(__('ai_assistant.review_saved'))->success()->send();
    }
}
