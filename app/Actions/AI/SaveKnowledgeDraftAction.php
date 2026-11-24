<?php

namespace App\Actions\AI;

use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeVersion;
use App\Models\Business;
use App\Models\User;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAssistantManager;
use App\Services\AI\AiAccess;
use App\Services\AI\AiUnavailable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

final class SaveKnowledgeDraftAction
{
    public function __construct(private readonly AiAssistantManager $manager) {}

    public function execute(Business $business, User $author, array $input, ?string $documentId = null): AiKnowledgeVersion
    {
        AiAccess::business('manage_ai_knowledge', $business->id);

        $data = Validator::make($input, [
            'title' => ['required', 'string', 'max:200'],
            'category' => ['nullable', 'string', 'max:100'],
            'locale' => ['required', 'in:en,pl,pt'],
            'content' => ['required', 'string', 'min:10', 'max:'.config('ai_assistant.max_document_chars')],
        ])->validate();

        // Caller must have active membership; never infer tenant from a public request.
        if (! $business->is_active || ! $author->businessMemberships()
            ->where('business_id', $business->id)->where('is_active', true)->exists()) {
            throw new AiUnavailable('tenant_forbidden');
        }

        $assistant = $this->manager->forBusiness($business);

        return DB::transaction(function () use ($business, $author, $data, $assistant, $documentId) {
            if ($documentId !== null) {
                $document = AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
                    ->where('business_id', $business->id)
                    ->where('assistant_id', $assistant->id)
                    ->whereKey($documentId)
                    ->lockForUpdate()
                    ->firstOrFail();

                if ($document->archived_at !== null) {
                    throw new AiUnavailable('archived_document');
                }

                // Public metadata belongs to the active version. Do not change it while
                // creating drafts; versioned metadata requires a separate schema change.
                if ($document->active_version_id !== null
                    && ($document->title !== trim($data['title'])
                        || ($document->category ?? null) !== ($data['category'] ?? null)
                        || $document->locale !== $data['locale'])) {
                    throw new AiUnavailable('published_metadata_immutable');
                }
            } else {
                $document = new AiKnowledgeDocument;
                $document->forceFill([
                    'business_id' => $business->id,
                    'assistant_id' => $assistant->id,
                ]);
            }

            $document->forceFill([
                'title' => trim($data['title']),
                'category' => $data['category'] ?? null,
                'locale' => $data['locale'],
            ])->save();

            $next = (int) AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
                ->where('business_id', $business->id)
                ->where('document_id', $document->id)
                ->max('version_no') + 1;

            $version = new AiKnowledgeVersion;
            $version->forceFill([
                'business_id' => $business->id,
                'document_id' => $document->id,
                'version_no' => $next,
                'content' => trim($data['content']),
                'checksum' => hash('sha256', trim($data['content'])),
                'status' => 'draft',
                'created_by' => $author->id,
            ])->save();

            return $version;
        });
    }
}
