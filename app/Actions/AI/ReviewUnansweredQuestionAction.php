<?php

namespace App\Actions\AI;

use App\Models\AiUnansweredQuestion;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;

final class ReviewUnansweredQuestionAction
{
    public function execute(string $questionId, string $status): void
    {
        $business = AiAccess::business('review_ai_questions');

        if (! in_array($status, ['reviewed', 'resolved', 'open'], true)) {
            throw new \InvalidArgumentException('Invalid review status.');
        }

        AiUnansweredQuestion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->whereKey($questionId)
            ->update([
                'status' => $status,
                'reviewed_at' => $status === 'open' ? null : now(),
                'reviewed_by' => $status === 'open' ? null : auth()->id(),
            ]);
        // No automatic training. The owner must author and publish knowledge manually.
    }
}
