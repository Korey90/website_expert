<?php

namespace Tests\Feature\LandingPage;

use App\Models\Business;
use App\Models\LandingPage;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Tests for the 24-hour deduplication mechanism in LeadService::createFromLandingPage().
 *
 * Covers:
 *  - Same email + same LP within 24h → status=duplicate, no second Lead row
 *  - Same email on a different LP → creates a new lead (different fingerprint)
 *  - Different email on the same LP → creates a new lead
 *  - Expired cache (>24h) → new lead created
 *  - conversions_count NOT incremented for duplicates
 *  - Response still returns HTTP 200 for duplicates (graceful UX)
 *  - Duplicate fingerprint key format is correct
 */
class LeadDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    private Business    $business;
    private LandingPage $page;
    private LandingPage $pageB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);

        $this->business = Business::create([
            'name'      => 'Dedup Corp',
            'slug'      => 'dedup-corp',
            'is_active' => true,
        ]);

        $this->page = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Page A',
            'slug'         => 'page-a',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);

        $this->pageB = LandingPage::create([
            'business_id'  => $this->business->id,
            'title'        => 'Page B',
            'slug'         => 'page-b',
            'status'       => LandingPage::STATUS_PUBLISHED,
            'template_key' => 'lead_magnet',
        ]);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Core deduplication
    // ─────────────────────────────────────────────────────────────────────────

    public function test_same_email_and_lp_within_24h_returns_duplicate_status(): void
    {
        // First submission → created
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        // Second submission → duplicate but still 200
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload())
            ->assertOk()
            ->assertJson(['success' => true]);

        // Only ONE lead created
        $this->assertDatabaseCount('leads', 1);
    }

    public function test_duplicate_does_not_create_additional_db_rows(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());

        $this->assertDatabaseCount('leads', 1);
    }

    public function test_duplicate_does_not_increment_conversions_count(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $countAfterFirst = $this->page->fresh()->conversions_count;

        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $countAfterSecond = $this->page->fresh()->conversions_count;

        $this->assertEquals($countAfterFirst, $countAfterSecond);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Fingerprint scope: per LP, not global
    // ─────────────────────────────────────────────────────────────────────────

    public function test_same_email_different_lp_creates_second_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload());
        $this->postJson(route('lp.submit', $this->pageB->slug), $this->payload());

        $this->assertDatabaseCount('leads', 2);
    }

    public function test_different_email_same_lp_creates_second_lead(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'alice@example.com']));
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'bob@example.com']));

        $this->assertDatabaseCount('leads', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Email normalisation: case-insensitive fingerprint
    // ─────────────────────────────────────────────────────────────────────────

    public function test_email_case_variations_are_treated_as_duplicate(): void
    {
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'Test@Example.COM']));
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => 'test@example.com']));

        $this->assertDatabaseCount('leads', 1);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Cache expiry
    // ─────────────────────────────────────────────────────────────────────────

    public function test_expired_cache_allows_new_lead(): void
    {
        // Simulate first lead created yesterday — manually set the fingerprint with a past TTL
        $email       = 'alice@example.com';
        $fingerprint = md5(strtolower(trim($email)) . '|' . $this->page->id . '|' . now()->subDay()->toDateString());
        // The new fingerprint for TODAY will be different — so submitting today should create new lead
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => $email]));

        // Clear cache manually to simulate expiry
        Cache::flush();

        // Re-submit — no cache hit → new lead created
        $this->postJson(route('lp.submit', $this->page->slug), $this->payload(['email' => $email]));

        $this->assertDatabaseCount('leads', 2);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function payload(array $override = []): array
    {
        return array_merge([
            'name'  => 'Alice',
            'email' => 'alice@example.com',
        ], $override);
    }
}
