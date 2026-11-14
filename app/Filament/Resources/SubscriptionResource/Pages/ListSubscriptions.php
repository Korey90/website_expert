<?php

namespace App\Filament\Resources\SubscriptionResource\Pages;

use App\Filament\Resources\SubscriptionResource;
use Filament\Resources\Pages\ListRecords;

class ListSubscriptions extends ListRecords
{
    protected static string $resource = SubscriptionResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.administration.subscriptions.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
