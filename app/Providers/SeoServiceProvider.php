<?php

namespace App\Providers;

use App\Services\Seo\DataForSeoProviderService;
use App\Services\Seo\ManualSerpProviderService;
use App\Services\Seo\SerpProviderInterface;
use Illuminate\Support\ServiceProvider;

class SeoServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(SerpProviderInterface::class, function ($app) {
            return match (config('seo.rank_checker.provider', 'manual')) {
                'dataforseo' => $app->make(DataForSeoProviderService::class),
                default => $app->make(ManualSerpProviderService::class),
            };
        });
    }

    public function boot(): void {}
}
