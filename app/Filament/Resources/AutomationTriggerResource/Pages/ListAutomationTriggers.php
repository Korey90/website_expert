<?php

namespace App\Filament\Resources\AutomationTriggerResource\Pages;

use App\Filament\Resources\AutomationTriggerResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutomationTriggers extends ListRecords
{
    protected static string $resource = AutomationTriggerResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.triggers.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
