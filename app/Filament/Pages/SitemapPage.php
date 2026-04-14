<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class SitemapPage extends Page
{
    protected string $view = 'filament.pages.sitemap';

    protected static \BackedEnum|string|null $navigationIcon  = 'heroicon-o-map';
    protected static \UnitEnum|string|null   $navigationGroup = 'Settings';
    protected static ?string $navigationLabel = 'Sitemap';
    protected static ?int    $navigationSort  = 25;

    public string $sitemapUrl = '';

    public function mount(): void
    {
        $this->sitemapUrl = url('/sitemap.xml');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh Sitemap')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->requiresConfirmation()
                ->modalHeading('Refresh Sitemap Cache')
                ->modalDescription('This will clear the cached sitemap and ping Google & Bing to re-index it. Continue?')
                ->modalSubmitActionLabel('Refresh & Ping')
                ->action(function () {
                    Cache::forget('sitemap.xml');

                    $sitemapUrl  = urlencode(url('/sitemap.xml'));
                    $pinged      = [];
                    $errors      = [];

                    $endpoints = [
                        'Google' => "https://www.google.com/ping?sitemap={$sitemapUrl}",
                        'Bing'   => "https://www.bing.com/ping?sitemap={$sitemapUrl}",
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

                    $body = 'Cache cleared.';
                    if ($pinged) {
                        $body .= ' Pinged: ' . implode(', ', $pinged) . '.';
                    }
                    if ($errors) {
                        $body .= ' Failed: ' . implode(', ', $errors) . '.';
                    }

                    Notification::make()
                        ->title('Sitemap refreshed')
                        ->body($body)
                        ->success()
                        ->send();
                }),
        ];
    }
}
