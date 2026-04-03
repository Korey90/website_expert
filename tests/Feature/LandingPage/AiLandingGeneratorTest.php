<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\LandingPageAiGeneration;
use App\Models\LandingPageGenerationVariant;
use App\Models\User;
use App\Services\LandingPage\OpenAiLandingClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for the AI Landing Page Generator endpoints.
 *
 * All tests mock OpenAiLandingClient so no real HTTP calls are made.
 *
 * Endpoints covered:
 *   POST /landing-pages/ai/generate                   → generate()
 *   POST /landing-pages/ai/variants/{variant}/regenerate-section → regenerateSection()
 *   POST /landing-pages/ai/variants/{variant}/save     → save()
 */
class AiLandingGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private User     $user;
    private Business $business;

    /** Minimal valid OpenAI response for a full landing page draft */
    private array $fakeAiDraft;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->business = Business::create([
            'name'      => 'Test Agency',
            'slug'      => 'test-agency',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create(['is_active' => true]);
        $this->user->assignRole('admin');
        $this->user->businesses()->attach($this->business->id, [
            'role'      => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->fakeAiDraft = [
            'content' => [
                'title'            => 'Boost Your Business',
                'slug_suggestion'  => 'boost-your-business',
                'language'         => 'en',
                'template_key'     => null,
                'meta'             => [
                    'meta_title'       => 'Boost Your Business',
                    'meta_description' => 'We help you grow.',
                    'conversion_goal'  => 'contact',
                ],
                'sections' => [
                    [
                        'type'     => 'hero',
                        'content'  => [
                            'headline'    => 'Grow with a focused landing page',
                            'subheadline' => 'Turn visitors into qualified leads.',
                            'cta_text'    => 'Get Started',
                            'cta_url'     => '#form',
                            'image_path'  => null,
                        ],
                        'settings' => ['background' => 'white', 'padding' => 'lg', 'visible' => true],
                    ],
                    [
                        'type'     => 'form',
                        'content'  => [
                            'headline'        => 'Get in touch',
                            'subheadline'     => 'We will get back to you shortly.',
                            'fields'          => ['name', 'email'],
                            'required'        => ['name', 'email'],
                            'cta_text'        => 'Send',
                            'success_message' => 'Thank you!',
                            'redirect_url'    => null,
                        ],
                        'settings' => ['background' => 'white', 'padding' => 'md', 'visible' => true],
                    ],
                ],
            ],
            'model' => 'gpt-4o-mini',
            'tokens_input'  => 120,
            'tokens_output' => 280,
        ];
    }

    // -------------------------------------------------------------------------
    // POST /landing-pages/ai/generate
    // -------------------------------------------------------------------------

    public function test_generate_returns_variant_on_success(): void
    {
        $this->mockOpenAiReturns($this->fakeAiDraft);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.generate'), [
                'goal' => 'contact',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'variant' => ['id', 'title', 'slug_suggestion', 'sections'],
            ]);

        $this->assertDatabaseHas('landing_page_generation_variants', [
            'business_id' => $this->business->id,
            'user_id'     => $this->user->id,
            'title'       => 'Boost Your Business',
        ]);
    }

    public function test_generate_requires_valid_goal(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.generate'), [
                'goal' => 'invalid_goal',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['goal']);
    }

    public function test_generate_returns_503_when_api_key_not_configured(): void
    {
        config(['services.openai.api_key' => '']);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.generate'), [
                'goal' => 'contact',
            ]);

        $response->assertStatus(503)
            ->assertJsonPath('success', false)
            ->assertJsonPath('error_code', 'openai_not_configured');
    }

    public function test_generate_returns_403_when_user_lacks_permission(): void
    {
        $restricted = User::factory()->create(['is_active' => true]);
        $restricted->assignRole('developer');
        $restricted->businesses()->attach($this->business->id, [
            'role'      => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $response = $this->actingAs($restricted)
            ->postJson(route('landing-pages.ai.generate'), [
                'goal' => 'contact',
            ]);

        $response->assertForbidden();
    }

    public function test_generate_returns_401_for_guests(): void
    {
        $response = $this->postJson(route('landing-pages.ai.generate'), [
            'goal' => 'contact',
        ]);

        $response->assertUnauthorized();
    }

    public function test_generate_saves_ai_generation_record_as_succeeded(): void
    {
        $this->mockOpenAiReturns($this->fakeAiDraft);

        $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.generate'), [
                'goal'        => 'book_call',
                'description' => 'We build websites for small businesses.',
            ]);

        $this->assertDatabaseHas('landing_page_ai_generations', [
            'business_id' => $this->business->id,
            'user_id'     => $this->user->id,
            'status'      => 'succeeded',
        ]);
    }

    public function test_generate_marks_generation_as_failed_when_openai_errors(): void
    {
        $this->app->instance(OpenAiLandingClient::class, new class extends OpenAiLandingClient {
            public function generateStructuredLanding(array $payload): array
            {
                throw new \App\Exceptions\LandingPageGenerationException(
                    'OpenAI error',
                    'openai_request_failed',
                    502,
                );
            }
        });

        $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.generate'), ['goal' => 'contact']);

        $this->assertDatabaseHas('landing_page_ai_generations', [
            'business_id' => $this->business->id,
            'status'      => 'failed',
        ]);
    }

    // -------------------------------------------------------------------------
    // POST /landing-pages/ai/variants/{variant}/regenerate-section
    // -------------------------------------------------------------------------

    public function test_regenerate_section_updates_existing_variant(): void
    {
        $variant = $this->createVariantForCurrentBusiness();

        $fakeSectionResult = [
            'content' => [
                'section' => [
                    'type'     => 'hero',
                    'content'  => [
                        'headline'    => 'Regenerated Hero Headline',
                        'subheadline' => 'New subheadline text',
                        'cta_text'    => 'Try Now',
                        'cta_url'     => '#form',
                    ],
                    'settings' => ['background' => 'dark', 'padding' => 'lg', 'visible' => true],
                ],
            ],
            'model'         => 'gpt-4o-mini',
            'tokens_input'  => 80,
            'tokens_output' => 160,
        ];

        $this->mockOpenAiReturns($fakeSectionResult, 'regenerateSection');

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.regenerate-section', $variant), [
                'section_type' => 'hero',
                'instruction'  => 'Make it more direct.',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['success', 'message', 'variant']);

        $variant->refresh();
        $heroSection = collect($variant->sections)->firstWhere('type', 'hero');
        $this->assertSame('Regenerated Hero Headline', $heroSection['content']['headline']);
    }

    public function test_regenerate_section_returns_404_for_another_tenants_variant(): void
    {
        $otherBusiness = Business::create([
            'name'      => 'Other Agency',
            'slug'      => 'other-agency',
            'is_active' => true,
        ]);

        $foreignVariant = LandingPageGenerationVariant::create([
            'business_id'     => $otherBusiness->id,
            'generation_id'   => LandingPageAiGeneration::create([
                'business_id' => $otherBusiness->id, 'user_id' => $this->user->id,
                'status' => LandingPageAiGeneration::STATUS_PENDING, 'source' => 'test', 'input_payload' => [],
            ])->id,
            'user_id'         => $this->user->id,
            'title'           => 'Foreign Page',
            'slug_suggestion' => 'foreign-page',
            'language'        => 'en',
            'sections'        => $this->fakeAiDraft['content']['sections'],
            'expires_at'      => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.regenerate-section', $foreignVariant), [
                'section_type' => 'hero',
            ]);

        // Middleware EnsureLandingPageTenantAccess → 404 (nie ujawnia istnienia cudzego zasobu)
        $response->assertNotFound();
    }

    public function test_regenerate_section_returns_422_for_expired_variant(): void
    {
        $variant = $this->createVariantForCurrentBusiness(['expires_at' => now()->subDay()]);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.regenerate-section', $variant), [
                'section_type' => 'hero',
            ]);

        $response->assertStatus(410)
            ->assertJsonPath('error_code', 'variant_expired');
    }

    // -------------------------------------------------------------------------
    // POST /landing-pages/ai/variants/{variant}/save
    // -------------------------------------------------------------------------

    public function test_save_creates_landing_page_from_variant(): void
    {
        $variant = $this->createVariantForCurrentBusiness();

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.save', $variant), []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'message',
                'landing_page' => ['id', 'title', 'slug'],
                'redirect_url',
            ]);

        $this->assertDatabaseHas('landing_pages', [
            'business_id' => $this->business->id,
            'title'       => $variant->title,
        ]);

        $variant->refresh();
        $this->assertTrue((bool) $variant->is_saved);
    }

    public function test_save_returns_422_when_variant_already_saved(): void
    {
        $variant = $this->createVariantForCurrentBusiness(['is_saved' => true]);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.save', $variant), []);

        $response->assertUnprocessable()
            ->assertJsonPath('error_code', 'variant_already_saved');
    }

    public function test_save_returns_403_for_another_tenants_variant(): void
    {
        $otherBusiness = Business::create([
            'name'      => 'Rival Agency',
            'slug'      => 'rival-agency',
            'is_active' => true,
        ]);

        $foreignVariant = LandingPageGenerationVariant::create([
            'business_id'     => $otherBusiness->id,
            'generation_id'   => LandingPageAiGeneration::create([
                'business_id' => $otherBusiness->id, 'user_id' => $this->user->id,
                'status' => LandingPageAiGeneration::STATUS_PENDING, 'source' => 'test', 'input_payload' => [],
            ])->id,
            'user_id'         => $this->user->id,
            'title'           => 'Rival Page',
            'slug_suggestion' => 'rival-page',
            'language'        => 'en',
            'sections'        => $this->fakeAiDraft['content']['sections'],
            'expires_at'      => now()->addDays(7),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.save', $foreignVariant), []);

        // Middleware EnsureLandingPageTenantAccess → 404 (nie ujawnia istnienia cudzego zasobu)
        $response->assertNotFound();
    }

    public function test_save_allows_overriding_title_and_slug(): void
    {
        $variant = $this->createVariantForCurrentBusiness();

        $response = $this->actingAs($this->user)
            ->postJson(route('landing-pages.ai.save', $variant), [
                'title' => 'Custom Title Override',
                'slug'  => 'custom-title-override',
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('landing_pages', [
            'business_id' => $this->business->id,
            'title'       => 'Custom Title Override',
            'slug'        => 'custom-title-override',
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Bind a fake OpenAiLandingClient that returns $fakeResult on the given method.
     */
    private function mockOpenAiReturns(array $fakeResult, string $method = 'generateStructuredLanding'): void
    {
        $mock = \Mockery::mock(OpenAiLandingClient::class);
        $mock->shouldReceive($method)
            ->once()
            ->andReturn($fakeResult);

        $this->app->instance(OpenAiLandingClient::class, $mock);
    }

    /**
     * Create a LandingPageGenerationVariant belonging to the current business.
     *
     * @param  array<string, mixed>  $overrides
     */
    private function createVariantForCurrentBusiness(array $overrides = []): LandingPageGenerationVariant
    {
        $generation = LandingPageAiGeneration::create([
            'business_id'   => $this->business->id,
            'user_id'       => $this->user->id,
            'status'        => LandingPageAiGeneration::STATUS_PENDING,
            'source'        => 'test',
            'input_payload' => [],
        ]);

        return LandingPageGenerationVariant::create(array_merge([
            'business_id'     => $this->business->id,
            'generation_id'   => $generation->id,
            'user_id'         => $this->user->id,
            'title'           => 'Boost Your Business',
            'slug_suggestion' => 'boost-your-business',
            'language'        => 'en',
            'template_key'    => null,
            'meta'            => [
                'meta_title'       => 'Boost Your Business',
                'meta_description' => 'We help you grow.',
                'conversion_goal'  => 'contact',
            ],
            'sections'    => $this->fakeAiDraft['content']['sections'],
            'expires_at'  => now()->addDays(7),
            'is_saved'    => false,
        ], $overrides));
    }
}
