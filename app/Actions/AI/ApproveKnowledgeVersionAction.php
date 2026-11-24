<?php

namespace App\Actions\AI;

use App\Jobs\IndexAiKnowledgeVersionJob;
use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeVersion;
use App\Models\Business;
use App\Models\User;
use App\Scopes\BusinessScope;
use App\Services\AI\AiUnavailable;
use Illuminate\Support\Facades\DB;

final class ApproveKnowledgeVersionAction
{
    public function execute(Business $business, User $approver, string $documentId, string $versionId): void
    {
        if (! $business->is_active || ! $approver->businessMemberships()
            ->where('business_id', $business->id)->where('is_active', true)->exists()) {
            throw new AiUnavailable('tenant_forbidden');
        }

        DB::transaction(function () use ($business, $approver, $documentId, $versionId) {
            $document = AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)->whereKey($documentId)
                ->lockForUpdate()->firstOrFail();

            $version = AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->where('document_id', $document->id)
                ->whereKey($versionId)
                ->lockForUpdate()->firstOrFail();

            if ($document->archived_at !== null || ! in_array($version->status, ['draft', 'failed'], true)) {
                throw new AiUnavailable('invalid_publication_state');
            }

            $version->forceFill([
                'status' => 'indexing',
                'approved_by' => $approver->id,
                'approved_at' => now(),
                'error_code' => null,
            ])->save();

            IndexAiKnowledgeVersionJob::dispatch($business->id, $version->id)
                ->onQueue('ai')->afterCommit();
        });
    }
}
