<?php

namespace App\Actions\AI;

use App\Models\Business;
use App\Services\AI\AiAccess;
use App\Services\AI\AiAssistantManager;
use App\Services\AI\AiUnavailable;
use App\Services\AI\GroundedAnswerService;
use Illuminate\Support\Facades\Validator;

final class TestAiKnowledgeAction
{
    public function __construct(
        private readonly AiAssistantManager $manager,
        private readonly GroundedAnswerService $answerer,
    ) {}

    /** @return array{status:string,answer:string,sources:array} */
    public function execute(Business $business, array $input): array
    {
        $data = Validator::make($input, [
            'question' => ['required', 'string', 'min:2', 'max:'.config('ai_assistant.max_question_chars')],
            'locale' => ['required', 'in:en,pl,pt'],
        ])->validate();

        $business = AiAccess::business('test_ai_knowledge', $business->id);
        if (! config('ai_assistant.preview_enabled')) {
            throw new AiUnavailable('disabled_or_unconfigured');
        }

        $assistant = $this->manager->forBusiness($business);

        return $this->answerer->answer($business->id, $assistant->id, $data['question'], $data['locale']);
    }
}
