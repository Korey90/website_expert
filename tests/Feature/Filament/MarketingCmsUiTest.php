<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\NavbarSettingsPage;
use App\Filament\Resources\ArticleResource;
use App\Filament\Resources\LandingPageResource;
use App\Filament\Resources\PageResource;
use App\Filament\Resources\SiteSectionResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCmsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_navigation_and_cms_lists_render_context_and_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $this->get(NavbarSettingsPage::getUrl())
            ->assertOk()
            ->assertSeeText(__('admin.migration.marketing.navigation.subheading'))
            ->assertSeeText(__('admin.migration.marketing.navigation.empty_heading'));

        $resources = [
            LandingPageResource::class => 'landing_pages',
            PageResource::class => 'pages',
            SiteSectionResource::class => 'sections',
            ArticleResource::class => 'articles',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.empty_heading"));
        }
    }
}
