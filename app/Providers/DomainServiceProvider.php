<?php

namespace App\Providers;

use App\Services\Domain\DomainRegistrarInterface;
use App\Services\Domain\ManualDomainRegistrarService;
use App\Services\Domain\OpenSrsRegistrarService;
use App\Services\Domain\OpenProviderRegistrarService;
use Illuminate\Support\ServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(DomainRegistrarInterface::class, function ($app) {
            return match (config('services.domain_registrar.provider', 'manual')) {
                'opensrs'       => $app->make(OpenSrsRegistrarService::class),
                'openprovider'  => $app->make(OpenProviderRegistrarService::class),
                default         => $app->make(ManualDomainRegistrarService::class),
            };
        });
    }

    public function boot(): void {}
}
