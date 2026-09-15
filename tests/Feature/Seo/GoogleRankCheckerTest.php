<?php

namespace Tests\Feature\Seo;

use App\Data\Seo\SerpOrganicResult;
use App\Data\Seo\SerpSearchResult;
use App\Exceptions\Seo\SerpProviderException;
use App\Models\SearchRankingCheck;
use App\Services\Seo\SerpProviderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Feature tests for the public Google Rank Checker tool.
 *
 * Covers:
 *  - Public page renders without auth
 *  - Validation: missing fields, invalid domain
 *  - found / not_found / failed outcomes (provider mocked)
 *  - failed status is never reported as not_found
 *  - identical checks are served from cache (provider called once)
 *  - throttle: 11th check within 60 minutes → 429
 */
class GoogleRankCheckerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    private function bindFakeProvider(array $organicResults): void
    {
        $this->mock(SerpProviderInterface::class, function ($mock) use ($organicResults) {
            $mock->shouldReceive('searchOrganicResults')
                ->andReturn(new SerpSearchResult(
                    keyword: 'web design belfast',
                    location: 'Belfast, United Kingdom',
                    device: 'desktop',
                    language: 'en',
                    organicResults: $organicResults,
                    provider: 'fake',
                ));
        });
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'keyword' => 'web design belfast',
            'website' => 'website-expert.uk',
            'location' => 'Belfast, United Kingdom',
            'device' => 'desktop',
        ], $overrides);
    }

    public function test_public_page_renders_without_auth(): void
    {
        $this->get(route('tools.google-rank-checker'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->component('Tools/GoogleRankChecker'));
    }

    public function test_validation_fails_for_missing_fields(): void
    {
        $this->postJson(route('tools.google-rank-checker.check'), [])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['keyword', 'website', 'location']);
    }

    public function test_validation_fails_for_invalid_domain(): void
    {
        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload(['website' => 'not a domain']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    public function test_found_result_returns_position_and_ranking_url(): void
    {
        $this->bindFakeProvider([
            new SerpOrganicResult(position: 1, url: 'https://competitor.com/'),
            new SerpOrganicResult(position: 7, url: 'https://website-expert.uk/services/web-design-belfast'),
        ]);

        $response = $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertOk()
            ->assertJson([
                'status' => 'found',
                'position' => 7,
                'ranking_url' => 'https://website-expert.uk/services/web-design-belfast',
                'domain' => 'website-expert.uk',
                'from_cache' => false,
            ]);

        $this->assertDatabaseHas('search_ranking_checks', [
            'domain' => 'website-expert.uk',
            'status' => SearchRankingCheck::STATUS_FOUND,
            'position' => 7,
        ]);
    }

    public function test_not_found_result_when_domain_absent_from_results(): void
    {
        $this->bindFakeProvider([
            new SerpOrganicResult(position: 1, url: 'https://competitor.com/'),
        ]);

        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertOk()
            ->assertJson([
                'status' => 'not_found',
                'position' => null,
            ]);

        $this->assertDatabaseHas('search_ranking_checks', [
            'domain' => 'website-expert.uk',
            'status' => SearchRankingCheck::STATUS_NOT_FOUND,
        ]);
    }

    public function test_provider_failure_is_reported_as_failed_not_not_found(): void
    {
        $this->mock(SerpProviderInterface::class, function ($mock) {
            $mock->shouldReceive('searchOrganicResults')
                ->andThrow(new SerpProviderException('SERP provider timeout.'));
        });

        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertOk()
            ->assertJson(['status' => 'failed']);

        $this->assertDatabaseHas('search_ranking_checks', [
            'domain' => 'website-expert.uk',
            'status' => SearchRankingCheck::STATUS_FAILED,
        ]);

        // A provider outage must never be cached as a real result
        $this->assertDatabaseMissing('search_ranking_checks', [
            'domain' => 'website-expert.uk',
            'status' => SearchRankingCheck::STATUS_NOT_FOUND,
        ]);
    }

    public function test_identical_check_is_served_from_cache(): void
    {
        $this->mock(SerpProviderInterface::class, function ($mock) {
            $mock->shouldReceive('searchOrganicResults')
                ->once()
                ->andReturn(new SerpSearchResult(
                    keyword: 'web design belfast',
                    location: 'Belfast, United Kingdom',
                    device: 'desktop',
                    language: 'en',
                    organicResults: [new SerpOrganicResult(position: 4, url: 'https://website-expert.uk/')],
                    provider: 'fake',
                ));
        });

        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertOk()
            ->assertJson(['from_cache' => false]);

        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertOk()
            ->assertJson(['from_cache' => true, 'position' => 4]);
    }

    public function test_rate_limit_blocks_the_eleventh_check_within_the_window(): void
    {
        $this->bindFakeProvider([
            new SerpOrganicResult(position: 1, url: 'https://competitor.com/'),
        ]);

        for ($i = 1; $i <= 10; $i++) {
            $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
                ->assertOk();
        }

        $this->postJson(route('tools.google-rank-checker.check'), $this->validPayload())
            ->assertStatus(429);
    }
}
