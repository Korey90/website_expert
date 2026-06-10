<?php

namespace Tests\Feature\ServicePage;

use App\Models\ServicePage;
use App\Models\ServicePageBlock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Model-level tests for ServicePage and ServicePageBlock.
 *
 * Run: php artisan test --filter=ServicePageModelTest
 */
class ServicePageModelTest extends TestCase
{
    use RefreshDatabase;

    // ─────────────────────────────────────────────────────────────────────────
    // ServicePage model
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function service_page_can_be_created_with_factory(): void
    {
        $page = ServicePage::factory()->create();

        $this->assertDatabaseHas('service_pages', ['id' => $page->id]);
    }

    #[Test]
    public function slug_is_unique(): void
    {
        ServicePage::factory()->create(['slug' => 'my-slug']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        ServicePage::factory()->create(['slug' => 'my-slug']);
    }

    #[Test]
    public function title_is_a_translatable_field(): void
    {
        $page = ServicePage::factory()->create([
            'title' => ['pl' => 'Tytuł PL', 'en' => 'Title EN', 'pt' => 'Título PT'],
        ]);

        $this->assertSame('Tytuł PL',  $page->getTranslation('title', 'pl'));
        $this->assertSame('Title EN',  $page->getTranslation('title', 'en'));
        $this->assertSame('Título PT', $page->getTranslation('title', 'pt'));
    }

    #[Test]
    public function meta_fields_are_translatable(): void
    {
        $page = ServicePage::factory()->create([
            'meta_title'       => ['pl' => 'Meta PL', 'en' => 'Meta EN', 'pt' => ''],
            'meta_description' => ['pl' => 'Opis PL', 'en' => 'Desc EN', 'pt' => ''],
            'nav_label'        => ['pl' => 'Nav PL',  'en' => 'Nav EN',  'pt' => ''],
        ]);

        $this->assertSame('Meta PL', $page->getTranslation('meta_title', 'pl'));
        $this->assertSame('Meta EN', $page->getTranslation('meta_title', 'en'));
        $this->assertSame('Opis PL', $page->getTranslation('meta_description', 'pl'));
        $this->assertSame('Nav EN',  $page->getTranslation('nav_label', 'en'));
    }

    #[Test]
    public function is_published_casts_to_boolean(): void
    {
        $page = ServicePage::factory()->create(['is_published' => true]);
        $this->assertTrue($page->is_published);

        $page2 = ServicePage::factory()->unpublished()->create();
        $this->assertFalse($page2->is_published);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ServicePage → blocks() relationship
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function blocks_relation_returns_all_blocks_ordered_by_sort_order(): void
    {
        $page = ServicePage::factory()->create();
        ServicePageBlock::factory()->for($page)->create(['sort_order' => 5, 'type' => 'faq']);
        ServicePageBlock::factory()->for($page)->create(['sort_order' => 1, 'type' => 'hero']);
        ServicePageBlock::factory()->for($page)->inactive()->create(['sort_order' => 3, 'type' => 'cta_banner']);

        $blocks = $page->blocks()->get();

        $this->assertCount(3, $blocks);
        $this->assertSame('hero',       $blocks->first()->type);
        $this->assertSame('faq',        $blocks->last()->type);
    }

    #[Test]
    public function active_blocks_returns_only_active_blocks(): void
    {
        $page = ServicePage::factory()->create();
        ServicePageBlock::factory()->for($page)->create(['is_active' => true,  'sort_order' => 1]);
        ServicePageBlock::factory()->for($page)->create(['is_active' => false, 'sort_order' => 2]);
        ServicePageBlock::factory()->for($page)->create(['is_active' => true,  'sort_order' => 3]);

        $active = $page->activeBlocks()->get();

        $this->assertCount(2, $active);
        $active->each(fn ($b) => $this->assertTrue($b->is_active));
    }

    #[Test]
    public function active_blocks_are_ordered_by_sort_order(): void
    {
        $page = ServicePage::factory()->create();
        ServicePageBlock::factory()->for($page)->create(['is_active' => true, 'sort_order' => 99, 'type' => 'faq']);
        ServicePageBlock::factory()->for($page)->create(['is_active' => true, 'sort_order' => 1,  'type' => 'hero']);

        $blocks = $page->activeBlocks()->get();

        $this->assertSame('hero', $blocks->first()->type);
        $this->assertSame('faq',  $blocks->last()->type);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // ServicePageBlock model
    // ─────────────────────────────────────────────────────────────────────────

    #[Test]
    public function block_belongs_to_service_page(): void
    {
        $page  = ServicePage::factory()->create();
        $block = ServicePageBlock::factory()->for($page)->hero()->create();

        $this->assertTrue($block->servicePage->is($page));
    }

    #[Test]
    public function block_content_casts_to_array(): void
    {
        $block = ServicePageBlock::factory()->hero('My Title')->create();

        $this->assertIsArray($block->content);
        $this->assertArrayHasKey('heading_pl', $block->content);
    }

    #[Test]
    public function block_settings_casts_to_array(): void
    {
        $page  = ServicePage::factory()->create();
        $block = ServicePageBlock::factory()->for($page)->create(['settings' => ['bg_color' => '#fff']]);

        $this->assertSame(['bg_color' => '#fff'], $block->settings);
    }

    #[Test]
    public function block_types_constant_contains_all_eight_types(): void
    {
        $types = array_keys(ServicePageBlock::TYPES);

        $this->assertCount(8, $types);
        $this->assertContains('hero',             $types);
        $this->assertContains('features_grid',    $types);
        $this->assertContains('packages',         $types);
        $this->assertContains('pricing_table',    $types);
        $this->assertContains('faq',              $types);
        $this->assertContains('cta_banner',       $types);
        $this->assertContains('text_section',     $types);
        $this->assertContains('comparison_table', $types);
    }

    #[Test]
    public function deleting_service_page_cascades_to_blocks(): void
    {
        $page  = ServicePage::factory()->create();
        $block = ServicePageBlock::factory()->for($page)->create();

        $blockId = $block->id;

        $page->delete();

        $this->assertDatabaseMissing('service_page_blocks', ['id' => $blockId]);
    }
}
