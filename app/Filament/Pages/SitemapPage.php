<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SitemapPage extends BasePage
{
    protected string $view = 'filament.pages.sitemap';

    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-map';

    protected static \UnitEnum|string|null $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Sitemap';

    protected static ?int $navigationSort = 13;

    public string $sitemapUrl = '';

    public function mount(): void
    {
        $this->sitemapUrl = url('/sitemap.xml');
    }

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.sitemap.subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label(__('admin.migration.marketing.sitemap.refresh_label'))
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading(__('admin.migration.marketing.sitemap.refresh_heading'))
                ->modalDescription(__('admin.migration.marketing.sitemap.refresh_description'))
                ->modalSubmitActionLabel(__('admin.migration.marketing.sitemap.refresh_submit'))
                ->action(function () {
                    Cache::forget('sitemap.xml');

                    $sitemapUrl = urlencode(url('/sitemap.xml'));
                    $pinged = [];
                    $errors = [];

                    $endpoints = [
                        'Google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
                        'Bing' => "https://www.bing.com/ping?sitemap={$sitemapUrl}",
                    ];

                    foreach ($endpoints as $engine => $endpoint) {
                        try {
                            $response = Http::timeout(6)->get($endpoint);
                            if ($response->successful()) {
                                $pinged[] = $engine;
                            } else {
                                $errors[] = "{$engine} ({$response->status()})";
                            }
                        } catch (\Throwable) {
                            $errors[] = $engine;
                        }
                    }

                    $body = __('admin.migration.marketing.sitemap.cache_cleared');
                    if ($pinged) {
                        $body .= ' '.__('admin.migration.marketing.sitemap.pinged', ['engines' => implode(', ', $pinged)]);
                    }
                    if ($errors) {
                        $body .= ' '.__('admin.migration.marketing.sitemap.failed', ['engines' => implode(', ', $errors)]);
                    }

                    Notification::make()
                        ->title(__('admin.migration.marketing.sitemap.refreshed'))
                        ->body($body)
                        ->success()
                        ->send();
                }),
        ];
    }
}
