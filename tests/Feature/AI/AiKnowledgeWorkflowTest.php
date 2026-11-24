<?php

namespace Tests\Feature\AI;

use App\Actions\AI\ApproveKnowledgeVersionAction;
use App\Actions\AI\IndexKnowledgeVersionAction;
use App\Actions\AI\SaveKnowledgeDraftAction;
use App\Actions\AI\TestAiKnowledgeAction;
use App\Jobs\IndexAiKnowledgeVersionJob;
use App\Models\AiKnowledgeDocument;
use App\Models\AiKnowledgeVersion;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use App\Services\AI\AiAssistantManager;
use App\Services\AI\AiProvider;
use App\Services\AI\AiUnavailable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class AiKnowledgeWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Business $alpha;
    private Business $beta;
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->admin = User::factory()->create(['is_active' => true]);
        $this->admin->assignRole('admin');
        $this->alpha = Business::create(['name' => 'AI Alpha', 'slug' => 'ai-alpha', 'is_active' => true, 'locale' => 'en']);
        $this->beta = Business::create(['name' => 'AI Beta', 'slug' => 'ai-beta', 'is_active' => true, 'locale' => 'en']);

        foreach ([$this->alpha, $this->beta] as $business) {
            BusinessUser::create([
                'business_id' => $business->id, 'user_id' => $this->admin->id,
                'role' => 'owner', 'is_active' => true,
            ]);
        }

        $this->actingAs($this->admin);
        $this->withSession([User::CURRENT_BUSINESS_SESSION_KEY => $this->alpha->id]);

        config()->set('ai_assistant.preview_enabled', true);
        config()->set('ai_assistant.dimensions', 2);
        config()->set('ai_assistant.embedding_model', 'fake-embedding');
        config()->set('ai_assistant.similarity_threshold', 0.25);
        Queue::fake();

        $this->app->instance(AiProvider::class, new class implements AiProvider {
            public function embed(string $businessId, string $assistantId, array $texts): array
            {
                return array_map(fn (string $s): array => [1.0, 0.0], $texts);
            }

            public function selectEvidence(string $businessId, string $assistantId, string $question, array $sources): array
            {
                return [
                    'status' => 'answered',
                    'evidence' => [['id' => $sources[0]['id'], 'quote' => $sources[0]['text']]],
                ];
            }
        });
    }

    private function save(Business $business, string $content = 'Website price is 599 pounds including five pages.'): AiKnowledgeVersion
    {
        return app(SaveKnowledgeDraftAction::class)->execute($business, $this->admin, [
            'title' => 'Website pricing', 'locale' => 'en', 'content' => $content,
        ]);
    }

    public function test_draft_is_not_searchable_until_explicit_approval_and_indexing(): void
    {
        $version = $this->save($this->alpha);
        $this->assertSame('draft', $version->status);

        app(AiAssistantManager::class)->setEnabled($this->alpha, true);
        $this->assertSame('unknown', app(TestAiKnowledgeAction::class)->execute($this->alpha, [
            'question' => 'What is the website price?', 'locale' => 'en',
        ])['status']);

        app(ApproveKnowledgeVersionAction::class)->execute($this->alpha, $this->admin, $version->document_id, $version->id);
        Queue::assertPushed(IndexAiKnowledgeVersionJob::class);

        app(IndexKnowledgeVersionAction::class)->execute($this->alpha->id, $version->id);
        $this->assertSame('published', $version->fresh()->status);
        $this->assertSame($version->id, AiKnowledgeDocument::findOrFail($version->document_id)->active_version_id);

        $result = app(TestAiKnowledgeAction::class)->execute($this->alpha, [
            'question' => 'What is the website price?', 'locale' => 'en',
        ]);
        $this->assertSame('grounded', $result['status']);
        $this->assertStringContainsString('599 pounds', $result['answer']);
        $this->assertCount(1, $result['sources']);
    }

    public function test_cross_tenant_documents_and_approvals_are_rejected(): void
    {
        $version = $this->save($this->alpha);

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        app(ApproveKnowledgeVersionAction::class)->execute($this->beta, $this->admin, $version->document_id, $version->id);
    }

    public function test_published_document_metadata_cannot_be_changed_by_a_draft(): void
    {
        $version = $this->save($this->alpha);
        app(ApproveKnowledgeVersionAction::class)->execute($this->alpha, $this->admin, $version->document_id, $version->id);
        app(IndexKnowledgeVersionAction::class)->execute($this->alpha->id, $version->id);

        $this->expectException(AiUnavailable::class);
        app(SaveKnowledgeDraftAction::class)->execute($this->alpha, $this->admin, [
            'title' => 'Changed title', 'locale' => 'pl', 'content' => 'New price is 800 pounds.',
        ], $version->document_id);
    }

    public function test_unauthenticated_user_cannot_save_document(): void
    {
        auth()->logout();

        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);
        $this->save($this->alpha);
    }
}
