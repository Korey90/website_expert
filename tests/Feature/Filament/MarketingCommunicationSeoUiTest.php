<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\SitemapPage;
use App\Filament\Resources\EmailTemplateResource;
use App\Filament\Resources\SearchRankingCheckResource;
use App\Filament\Resources\SmsTemplateResource;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketingCommunicationSeoUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_communication_and_rankings_render_context_and_empty_states(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $resources = [
            EmailTemplateResource::class => 'email_templates',
            SmsTemplateResource::class => 'sms_templates',
            SearchRankingCheckResource::class => 'rankings',
        ];

        foreach ($resources as $resource => $translationKey) {
            $this->get($resource::getUrl('index'))
                ->assertOk()
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.list_subheading"))
                ->assertSeeText(__("admin.migration.marketing.{$translationKey}.empty_heading"));
        }
    }

    public function test_sitemap_renders_context_and_localized_sections(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();

        $this->actingAs($admin)
            ->get(SitemapPage::getUrl())
            ->assertOk()
            ->assertSeeText(__('admin.migration.marketing.sitemap.subheading'))
            ->assertSeeText(__('admin.migration.marketing.sitemap.url_heading'))
            ->assertSeeText(__('admin.migration.marketing.sitemap.included_heading'));
    }
}
