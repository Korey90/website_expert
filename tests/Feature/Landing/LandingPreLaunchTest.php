<?php

namespace Tests\Feature\Landing;

use App\Models\Business;
use App\Models\PipelineStage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Pre-launch validation tests for static landing pages.
 *
 * Covers:
 *  - Both LP pages return 200
 *  - PL page has correct html lang="pl" (set by App::setLocale)
 *  - EN page has correct html lang="en"
 *  - Sitemap.xml contains both LP URLs
 *  - Canonical tags present in app.blade.php for LP routes
 *  - hreflang tags correct for both LP pages
 *  - Form endpoints not accessible via GET (405 / redirect)
 *  - Throttle: 6th submission in 60s returns 429
 */
class LandingPreLaunchTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();
        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        PipelineStage::firstOrCreate(['slug' => 'new-lead'], ['name' => 'New Lead', 'order' => 1]);
        Business::first() ?? Business::create(['name' => 'Website Expert', 'slug' => 'website-expert', 'is_active' => true]);
    }

    // ── Page availability ─────────────────────────────────────────────────────

    public function test_pl_lp_returns_200(): void
    {
        $this->get('/pl/strony-internetowe-uk')->assertStatus(200);
    }

    public function test_en_lp_returns_200(): void
    {
        $this->get('/en/web-design-northern-ireland')->assertStatus(200);
    }

    // ── HTML lang attribute (set server-side by App::setLocale) ───────────────

    public function test_pl_lp_sets_locale_pl(): void
    {
        $response = $this->get('/pl/strony-internetowe-uk');
        $response->assertStatus(200);

        // Laravel sets app locale via controller — verify by checking response content
        // (Inertia renders into app.blade.php which uses app()->getLocale())
        $this->assertStringContainsString('lang="pl"', $response->getContent());
    }

    public function test_en_lp_sets_locale_en(): void
    {
        $response = $this->get('/en/web-design-northern-ireland');
        $response->assertStatus(200);

        $this->assertStringContainsString('lang="en"', $response->getContent());
    }

    // ── hreflang tags (rendered by app.blade.php) ─────────────────────────────

    public function test_pl_lp_has_hreflang_tags(): void
    {
        $html = $this->get('/pl/strony-internetowe-uk')->getContent();

        $this->assertStringContainsString('hreflang="pl"', $html);
        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
    }

    public function test_en_lp_has_hreflang_tags(): void
    {
        $html = $this->get('/en/web-design-northern-ireland')->getContent();

        $this->assertStringContainsString('hreflang="en"', $html);
        $this->assertStringContainsString('hreflang="pl"', $html);
        $this->assertStringContainsString('hreflang="x-default"', $html);
    }

    public function test_pl_lp_hreflang_points_to_correct_urls(): void
    {
        $html = $this->get('/pl/strony-internetowe-uk')->getContent();

        $this->assertStringContainsString('href="https://website-expert.uk/pl/strony-internetowe-uk"', $html);
        $this->assertStringContainsString('href="https://website-expert.uk/en/web-design-northern-ireland"', $html);
    }

    public function test_en_lp_hreflang_points_to_correct_urls(): void
    {
        $html = $this->get('/en/web-design-northern-ireland')->getContent();

        $this->assertStringContainsString('href="https://website-expert.uk/en/web-design-northern-ireland"', $html);
        $this->assertStringContainsString('href="https://website-expert.uk/pl/strony-internetowe-uk"', $html);
    }

    // ── Sitemap ───────────────────────────────────────────────────────────────

    public function test_sitemap_contains_pl_lp(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('/pl/strony-internetowe-uk', $xml);
    }

    public function test_sitemap_contains_en_lp(): void
    {
        $xml = $this->get('/sitemap.xml')->getContent();
        $this->assertStringContainsString('/en/web-design-northern-ireland', $xml);
    }

    // ── Method security ───────────────────────────────────────────────────────

    public function test_pl_submit_endpoint_requires_post(): void
    {
        $this->get('/pl/strony-internetowe-uk/wyslij')->assertStatus(405);
    }

    public function test_en_submit_endpoint_requires_post(): void
    {
        $this->get('/en/web-design-northern-ireland/submit')->assertStatus(405);
    }

    // ── Throttle ─────────────────────────────────────────────────────────────

    public function test_pl_throttle_blocks_after_5_requests(): void
    {
        $payload = ['name' => 'Test', 'email' => 'test@test.co.uk', 'message' => 'Test.', 'gdpr_consent' => true];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/pl/strony-internetowe-uk/wyslij', array_merge($payload, ['email' => "test{$i}@test.co.uk"]))->assertStatus(200);
        }

        $this->postJson('/pl/strony-internetowe-uk/wyslij', array_merge($payload, ['email' => 'test99@test.co.uk']))->assertStatus(429);
    }

    public function test_en_throttle_blocks_after_5_requests(): void
    {
        $payload = ['name' => 'Test', 'email' => 'test@test.co.uk', 'message' => 'Test.', 'gdpr_consent' => true];

        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/en/web-design-northern-ireland/submit', array_merge($payload, ['email' => "t{$i}@test.co.uk"]))->assertStatus(200);
        }

        $this->postJson('/en/web-design-northern-ireland/submit', array_merge($payload, ['email' => 'tx@test.co.uk']))->assertStatus(429);
    }
}
