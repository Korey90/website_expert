<?php

namespace App\Services\AI;

final class GroundedAnswerService
{
    public function __construct(
        private readonly AiKnowledgeRetriever $retriever,
        private readonly AiProvider $provider,
    ) {}

    /**
     * Only exact approved excerpts may be sent to a visitor.
     * No model-produced unsupported sentences are used.
     *
     * @return array{status:string,answer:string,sources:array}
     */
    public function answer(string $businessId, string $assistantId, string $question, string $locale): array
    {
        $unknown = ['status' => 'unknown', 'answer' => __('ai_assistant.unknown'), 'sources' => []];
        $matches = $this->retriever->search($businessId, $assistantId, $question, $locale);

        if ($matches === []) {
            return $unknown;
        }

        $sources = array_map(fn (array $row): array => ['id' => $row['id'], 'text' => $row['text']], $matches);
        $selected = $this->provider->selectEvidence($businessId, $assistantId, $question, $sources);

        if (($selected['status'] ?? null) !== 'answered' || ! is_array($selected['evidence'] ?? null)
            || count($selected['evidence']) < 1 || count($selected['evidence']) > 5) {
            return $unknown;
        }

        $trusted = collect($matches)->keyBy('id');
        $evidence = [];

        foreach ($selected['evidence'] as $item) {
            if (! is_array($item) || ! is_string($item['id'] ?? null) || ! is_string($item['quote'] ?? null)) {
                return $unknown;
            }

            $source = $trusted->get($item['id']);
            $quote = trim($item['quote']);
            if (! $source || mb_strlen($quote) < 3 || mb_strlen($quote) > 1800
                || ! str_contains($source['text'], $quote)) {
                return $unknown;
            }

            $evidence[$item['id']] = [
                'document_id' => $source['document_id'],
                'title' => $source['title'],
                'version' => $source['version'],
                'quote' => $quote,
                'score' => round($source['score'], 3),
            ];
        }

        if ($evidence === []) {
            return $unknown;
        }

        $used = array_values($evidence);

        return [
            'status' => 'grounded',
            'answer' => implode("\n\n", array_column($used, 'quote')),
            'sources' => $used,
        ];
    }
}
