<?php

namespace App\Actions\AI;

use App\Models\Business;
use App\Services\AI\AiAssistantManager;
use App\Services\AI\AiKnowledgeRetriever;
use App\Services\AI\AiProvider;
use App\Services\AI\AiUnavailable;
use Illuminate\Support\Facades\Validator;

final class TestAiKnowledgeAction
{
    public function __construct(
        private readonly AiAssistantManager $manager,
        private readonly AiKnowledgeRetriever $retriever,
        private readonly AiProvider $provider,
    ) {}

    /** @return array{status:string,answer:string,sources:list<array<string,mixed>>} */
    public function execute(Business $business, array $input): array
    {
        $data = Validator::make($input, [
            'question' => ['required', 'string', 'min:2', 'max:'.config('ai_assistant.max_question_chars')],
            'locale' => ['required', 'in:en,pl,pt'],
        ])->validate();

        $unknown = ['status' => 'unknown', 'answer' => __('ai_assistant.unknown'), 'sources' => []];

        if (! config('ai_assistant.preview_enabled')) {
            throw new AiUnavailable('disabled_or_unconfigured');
        }

        // Caller is authorized by AiAccess; business must match active authenticated tenant.
        $business = \App\Services\AI\AiAccess::business('test_ai_knowledge', $business->id);
        $assistant = $this->manager->forBusiness($business);

        $matches = $this->retriever->search($business->id, $assistant->id, $data['question'], $data['locale']);
        if ($matches === []) {
            return $unknown;
        }

        $sources = array_map(fn (array $match): array => [
            'id' => $match['id'],
            'text' => $match['text'],
        ], $matches);

        $result = $this->provider->selectEvidence($business->id, $assistant->id, $data['question'], $sources);
        if (($result['status'] ?? null) !== 'answered' || ! isset($result['evidence'])
            || ! is_array($result['evidence']) || count($result['evidence']) > 5) {
            return $unknown;
        }

        $trusted = collect($matches)->keyBy('id');
        $evidence = [];
        foreach ($result['evidence'] as $candidate) {
            if (! is_array($candidate) || ! is_string($candidate['id'] ?? null) || ! is_string($candidate['quote'] ?? null)) {
                return $unknown;
            }

            $source = $trusted->get($candidate['id']);
            $quote = trim($candidate['quote']);
            if (! $source || mb_strlen($quote) < 3 || mb_strlen($quote) > 1800
                || ! str_contains($source['text'], $quote)) {
                return $unknown;
            }

            if (! isset($evidence[$candidate['id']])) {
                $evidence[$candidate['id']] = [
                    'document_id' => $source['document_id'],
                    'title' => $source['title'],
                    'version' => $source['version'],
                    'quote' => $quote,
                    'score' => round($source['score'], 3),
                ];
            }
        }

        if ($evidence === []) {
            return $unknown;
        }

        // Use exact approved quotations only; a fluent paraphrase will be a later
        // feature requiring claim-level verification. The UI escapes text.
        return [
            'status' => 'grounded',
            'answer' => implode("\n\n", array_map(fn ($source) => $source['quote'], array_values($evidence))),
            'sources' => array_values($evidence),
        ];
    }
}
