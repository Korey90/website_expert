<?php

namespace App\Filament\Pages;

use App\Actions\AI\ApproveKnowledgeVersionAction;
use App\Actions\AI\SaveKnowledgeDraftAction;
use App\Actions\AI\TestAiKnowledgeAction;
use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeVersion;
use App\Scopes\BusinessScope;
use App\Services\AI\AiAccess;
use App\Services\AI\AiAssistantManager;
use App\Services\AI\AiUnavailable;
use Filament\Notifications\Notification;

class AiKnowledgePage extends BasePage
{
    protected string $view = 'filament.pages.ai-knowledge';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-sparkles';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'AI Knowledge';

    protected static ?int $navigationSort = 26;

    public string $titleInput = '';

    public string $categoryInput = '';

    public string $localeInput = 'en';

    public string $contentInput = '';

    public string $questionInput = '';

    public string $questionLocale = 'en';

    public ?string $editingDocument = null;

    public bool $assistantEnabled = false;

    /** @var array<string,mixed>|null */
    public ?array $testResult = null;

    public static function getNavigationLabel(): string
    {
        return __('ai_assistant.knowledge');
    }

    public function mount(AiAssistantManager $manager): void
    {
        $business = AiAccess::business('view_ai_knowledge');
        $assistant = $manager->forBusiness($business);
        $this->assistantEnabled = (bool) $assistant->enabled;
        $this->localeInput = in_array($business->locale, ['en','pl','pt'], true) ? $business->locale : 'en';
        $this->questionLocale = $this->localeInput;
    }

    public function documents(): array
    {
        $business = AiAccess::business('view_ai_knowledge');

        return AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)
            ->whereNull('archived_at')
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get()
            ->map(function (AiKnowledgeDocument $document) use ($business) {
                $versions = AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
                    ->where('business_id', $business->id)
                    ->where('document_id', $document->id)
                    ->orderByDesc('version_no')->limit(10)->get(['id','version_no','status']);

                return [
                    'id' => $document->id,
                    'title' => $document->title,
                    'locale' => $document->locale,
                    'active_version_id' => $document->active_version_id,
                    'versions' => $versions->toArray(),
                ];
            })->all();
    }

    public function edit(string $documentId): void
    {
        $business = AiAccess::business('manage_ai_knowledge');
        $doc = AiKnowledgeDocument::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)
            ->whereNull('archived_at')->findOrFail($documentId);

        $this->editingDocument = $doc->id;
        $this->titleInput = $doc->title;
        $this->categoryInput = $doc->category ?? '';
        $this->localeInput = $doc->locale;
        // Published content is read from immutable versions, never edited in place.
        $version = AiKnowledgeVersion::withoutGlobalScope(BusinessScope::class)
            ->where('business_id', $business->id)->where('document_id', $doc->id)
            ->orderByDesc('version_no')->first();
        $this->contentInput = $version?->content ?? '';
    }

    public function clearEditor(): void
    {
        AiAccess::business('manage_ai_knowledge');
        $this->editingDocument = null;
        $this->titleInput = '';
        $this->categoryInput = '';
        $this->contentInput = '';
    }

    public function save(SaveKnowledgeDraftAction $action): void
    {
        $business = AiAccess::business('manage_ai_knowledge');
        $version = $action->execute($business, auth()->user(), [
            'title' => $this->titleInput,
            'category' => $this->categoryInput === '' ? null : $this->categoryInput,
            'locale' => $this->localeInput,
            'content' => $this->contentInput,
        ], $this->editingDocument);

        $this->editingDocument = $version->document_id;
        Notification::make()->title(__('ai_assistant.saved'))->success()->send();
    }

    public function approve(string $documentId, string $versionId, ApproveKnowledgeVersionAction $action): void
    {
        $business = AiAccess::business('publish_ai_knowledge');
        $action->execute($business, auth()->user(), $documentId, $versionId);
        Notification::make()->title(__('ai_assistant.queued'))->success()->send();
    }

    public function toggleAssistant(AiAssistantManager $manager): void
    {
        $business = AiAccess::business('manage_ai_knowledge');
        $assistant = $manager->forBusiness($business);
        $this->assistantEnabled = (bool) $manager->setEnabled($business, ! $assistant->enabled)->enabled;
        Notification::make()->title(__('ai_assistant.settings_saved'))->success()->send();
    }

    public function test(TestAiKnowledgeAction $action): void
    {
        $business = AiAccess::business('test_ai_knowledge');

        try {
            $this->testResult = $action->execute($business, [
                'question' => $this->questionInput,
                'locale' => $this->questionLocale,
            ]);
        } catch (AiUnavailable $e) {
            $this->testResult = [
                'status' => 'unavailable',
                'answer' => __('ai_assistant.unavailable'),
                'sources' => [],
            ];
        }
    }
}
