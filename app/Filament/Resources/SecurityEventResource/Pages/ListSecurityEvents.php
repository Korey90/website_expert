<?php

namespace App\Filament\Resources\SecurityEventResource\Pages;

use App\Filament\Resources\SecurityEventResource;
use App\Filament\Widgets\SecurityStatsWidget;
use Filament\Resources\Pages\ListRecords;

class ListSecurityEvents extends ListRecords
{
    protected static string $resource = SecurityEventResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.security.list_subheading');
    }

    public function getTitle(): string
    {
        return __('admin.navigation.items.security_events');
    }

    protected function getHeaderWidgets(): array
    {
        return [SecurityStatsWidget::class];
    }
}
