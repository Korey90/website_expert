<?php

namespace Tests\Feature\ServicePage;

use App\Models\ServicePage;
use App\Models\ServicePageBlock;
use App\Models\SiteSection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * HTTP-level tests for ServicePageController.
 *
 * Run: php artisan test --filter=ServicePageControllerTest
 */
class ServicePageControllerTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // Happy path
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function published_page_returns_200_with_inertia_component(): void
    {
        $page = ServicePage::factory()->withFullTranslations()->create(['slug' => 'seo']);

        $response = $this->get('/seo');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Services/ServicePage')
        );
    }

    #[Test]
    public function unpublished_page_returns_404(): void
    {
        ServicePage::factory()->unpublished()->create(['slug' => 'hidden-page']);

        $this->get('/hidden-page')->assertStatus(404);
    }

    #[Test]
    public function non_existent_slug_returns_404(): void
    {
        $this->get('/slug-that-does-not-exist-xyz123')->assertStatus(404);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Blocks
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function active_blocks_are_passed_to_inertia_view(): void
    {
        $page = ServicePage::factory()->create(['slug' => 'hosting']);
        ServicePageBlock::factory()->hero()->for($page)->create(['sort_order' => 0]);
        ServicePageBlock::factory()->faq()->for($page)->create(['sort_order' => 1]);

        $this->get('/hosting')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->has('blocks', 2)
        );
    }

    #[Test]
    public function inactive_blocks_are_excluded_from_response(): void
    {
        $page = ServicePage::factory()->create(['slug' => 'test-page']);
        ServicePageBlock::factory()->hero()->for($page)->create(['sort_order' => 0, 'is_active' => true]);
        ServicePageBlock::factory()->faq()->for($page)->inactive()->create(['sort_order' => 1]);

        $this->get('/test-page')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->has('blocks', 1)
        );
    }

    #[Test]
    public function blocks_contain_expected_shape(): void
    {
        $page = ServicePage::factory()->create(['slug' => 'domains']);
        ServicePageBlock::factory()->hero('Hosting Premium')->for($page)->create(['sort_order' => 0]);

        $this->get('/domains')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->has('blocks.0', fn ($b) =>
                  $b->has('id')
                    ->has('type')
                    ->has('content')
                    ->has('settings')
              )
        );
    }

    #[Test]
    public function blocks_are_ordered_by_sort_order(): void
    {
        $page = ServicePage::factory()->create(['slug' => 'ordered-page']);
        ServicePageBlock::factory()->ofType('faq')->for($page)->create(['sort_order' => 10]);
        ServicePageBlock::factory()->hero()->for($page)->create(['sort_order' => 1]);

        $this->get('/ordered-page')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->where('blocks.0.type', 'hero')
              ->where('blocks.1.type', 'faq')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Inertia props structure
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function response_contains_page_slug_in_props(): void
    {
        ServicePage::factory()->withFullTranslations()->create(['slug' => 'seo-service']);

        $this->get('/seo-service')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->where('page.slug', 'seo-service')
        );
    }

    #[Test]
    public function response_contains_locale_prop(): void
    {
        ServicePage::factory()->create(['slug' => 'test-locale']);

        $this->get('/test-locale')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->has('locale')
        );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Locale resolution
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function session_locale_is_respected(): void
    {
        ServicePage::factory()->withFullTranslations()->create(['slug' => 'locale-test']);

        $this->withSession(['locale' => 'en'])
             ->get('/locale-test')
             ->assertInertia(fn ($p) =>
                 $p->where('locale', 'en')
             );
    }

    #[Test]
    public function page_title_resolves_to_session_locale(): void
    {
        ServicePage::factory()->withFullTranslations()->create(['slug' => 'title-locale-test']);

        $this->withSession(['locale' => 'en'])
             ->get('/title-locale-test')
             ->assertInertia(fn ($p) =>
                 $p->component('Services/ServicePage')
                   ->where('locale', 'en')
                   ->has('page.title')
             );
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Footer section
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function footer_prop_is_null_when_no_footer_section_exists(): void
    {
        ServicePage::factory()->create(['slug' => 'no-footer']);

        $this->get('/no-footer')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->where('footer', null)
        );
    }

    #[Test]
    public function footer_prop_is_populated_from_site_section(): void
    {
        ServicePage::factory()->create(['slug' => 'with-footer']);
        SiteSection::create([
            'key'       => 'footer',
            'label'     => 'Footer',
            'extra'     => ['address' => 'Test St. 1'],
            'is_active' => true,
        ]);

        $this->get('/with-footer')->assertInertia(fn ($p) =>
            $p->component('Services/ServicePage')
              ->has('footer')
        );
    }
}
