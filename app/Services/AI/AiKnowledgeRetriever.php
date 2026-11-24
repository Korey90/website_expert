<?php

namespace App\Services\AI;

use App\Models\AiKnowledgeChunk;
use App\Scopes\BusinessScope;

final class AiKnowledgeRetriever
{
    public function __construct(private readonly AiProvider $provider) {}

    /** @return list<array{id:string,document_id:string,title:string,version:int,locale:string,text:string,score:float}> */
    public function search(string $businessId, string $assistantId, string $question, string $locale): array
    {
        if (! in_array($locale, ['en', 'pl', 'pt'], true) || trim($question) === ''
            || mb_strlen($question) > config('ai_assistant.max_question_chars')) {
            throw new AiUnavailable('invalid_question');
        }

        // Explicit tenant + assistant + published active version + archived check.
        $query = AiKnowledgeChunk::withoutGlobalScope(BusinessScope::class)
            ->join('ai_knowledge_versions as v', function ($join) {
                $join->on('v.id', '=', 'ai_knowledge_chunks.version_id')
                    ->on('v.business_id', '=', 'ai_knowledge_chunks.business_id');
            })
            ->join('ai_knowledge_documents as d', function ($join) {
                $join->on('d.id', '=', 'v.document_id')
                    ->on('d.business_id', '=', 'v.business_id');
            })
            ->where('ai_knowledge_chunks.business_id', $businessId)
            ->where('d.assistant_id', $assistantId)
            ->where('d.locale', $locale)
            ->whereNull('d.archived_at')
            ->whereColumn('d.active_version_id', 'v.id')
            ->where('v.status', 'published')
            ->where('ai_knowledge_chunks.model', config('ai_assistant.embedding_model'))
            ->where('ai_knowledge_chunks.dimensions', (int) config('ai_assistant.dimensions'));

        $count = (clone $query)->count();
        if ($count < 1) {
            return [];
        }

        // For the pilot refuse an oversized index instead of silently scanning a partial set.
        if ($count > (int) config('ai_assistant.max_chunks')) {
            throw new AiUnavailable('index_too_large');
        }

        $vector = $this->provider->embed($businessId, $assistantId, [trim($question)])[0] ?? null;
        if (! is_array($vector) || ! AiVector::valid($vector, (int) config('ai_assistant.dimensions'))) {
            throw new AiUnavailable('invalid_embedding');
        }

        $rows = $query->select([
            'ai_knowledge_chunks.id',
            'ai_knowledge_chunks.embedding',
            'ai_knowledge_chunks.text',
            'd.id as document_id',
            'd.title',
            'd.locale',
            'v.version_no',
        ])->get();

        $results = [];
        foreach ($rows as $row) {
            $embedding = is_array($row->embedding) ? $row->embedding : json_decode((string) $row->embedding, true);
            if (! is_array($embedding) || ! AiVector::valid($embedding, count($vector))) {
                continue;
            }

            $score = AiVector::cosine($vector, $embedding);
            if ($score < (float) config('ai_assistant.similarity_threshold')) {
                continue;
            }

            $results[] = [
                'id' => (string) $row->id,
                'document_id' => (string) $row->document_id,
                'title' => (string) $row->title,
                'version' => (int) $row->version_no,
                'locale' => (string) $row->locale,
                'text' => (string) $row->text,
                'score' => $score,
            ];
        }

        usort($results, fn (array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_slice($results, 0, max(1, min(8, (int) config('ai_assistant.top_k'))));
    }
}
