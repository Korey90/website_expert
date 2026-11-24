<?php

namespace App\Actions\AI;

use App\Models\AiKnowledgeChunk;
use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeVersion;
use App\Models\Business;
use App\Scopes\BusinessScope;
use App\Services\AI\AiProvider;
use App\Services\AI\AiUnavailable;
use App\Services\AI\AiVector;
use App\Services\AI\KnowledgeChunker;
use Illuminate\Support\Facades\DB;

final class IndexKnowledgeVersionAction
{
    public function __construct(
        private readonly AiProvider $provider,
        private readonly KnowledgeChunker $chunker,
    ) {}

    public function execute(string $businessId, string $versionId): void
    {
        $business = Business::whereKey($businessId)->where('is_active', true)->first();
        if (! $business) {
            throw new AiUnavailable('invalid_tenant');
        }

        $version = AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $businessId)->whereKey($versionId)
            ->where('status', 'indexing')->first();

        if (! $version) {
            throw new AiUnavailable('invalid_publication_state');
        }

        $document = AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $businessId)->whereKey($version->document_id)
            ->whereNull('archived_at')->first();

        if (! $document || hash('sha256', $version->content) !== $version->checksum) {
            throw new AiUnavailable('invalid_document');
        }

        $chunks = $this->chunker->split($version->content);
        if (count($chunks) === 0 || count($chunks) > (int) config('ai_assistant.max_chunks')) {
            throw new AiUnavailable('too_many_chunks');
        }

        $vectors = [];
        foreach (array_chunk($chunks, 32, true) as $batch) {
            foreach ($this->provider->embed($businessId, $document->assistant_id, array_values($batch)) as $vector) {
                $vectors[] = $vector;
            }
        }

        $dimensions = (int) config('ai_assistant.dimensions');
        if (count($vectors) !== count($chunks)) {
            throw new AiUnavailable('invalid_embedding');
        }

        foreach ($vectors as $vector) {
            if (! AiVector::valid($vector, $dimensions)) {
                throw new AiUnavailable('invalid_embedding');
            }
        }

        DB::transaction(function () use ($businessId, $versionId, $document, $chunks, $vectors, $dimensions) {
            $locked = AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $businessId)->whereKey($document->id)
                ->lockForUpdate()->firstOrFail();

            $version = AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $businessId)->where('document_id', $locked->id)
                ->whereKey($versionId)->lockForUpdate()->firstOrFail();

            if ($locked->archived_at !== null || $version->status !== 'indexing' || hash('sha256', $version->content) !== $version->checksum) {
                throw new AiUnavailable('invalid_publication_state');
            }

            AiKnowledgeChunk::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $businessId)->where('version_id', $versionId)->delete();

            foreach ($chunks as $index => $content) {
                $chunk = new AiKnowledgeChunk;
                $chunk->forceFill([
                    'business_id' => $businessId,
                    'version_id' => $versionId,
                    'ordinal' => $index,
                    'text' => $content,
                    'content_hash' => hash('sha256', $content),
                    'embedding' => $vectors[$index],
                    'model' => (string) config('ai_assistant.embedding_model'),
                    'dimensions' => $dimensions,
                ])->save();
            }

            $version->forceFill(['status' => 'published', 'indexed_at' => now(), 'error_code' => null])->save();

            $active = $locked->active_version_id
                ? AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
                    ->where('business_id', $businessId)->whereKey($locked->active_version_id)->first()
                : null;

            // Jobs can finish out of order; never let an older version replace a newer one.
            if (! $active || $version->version_no > $active->version_no) {
                $locked->forceFill(['active_version_id' => $versionId])->save();
            }
        });
    }

    public function markFailed(string $businessId, string $versionId): void
    {
        AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $businessId)->whereKey($versionId)
            ->where('status', 'indexing')
            ->update(['status' => 'failed', 'error_code' => 'indexing_failed']);
    }
}
