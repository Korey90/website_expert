<?php

namespace App\Filament\Resources\PortfolioProjectResource\Pages;

use App\Filament\Resources\PortfolioProjectResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePortfolioProject extends CreateRecord
{
    protected static string $resource = PortfolioProjectResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.marketing.portfolio.create_subheading');
    }
}
