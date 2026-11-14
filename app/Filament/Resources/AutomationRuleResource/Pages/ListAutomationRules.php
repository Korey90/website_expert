<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutomationRules extends ListRecords
{
    protected static string $resource = AutomationRuleResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.rules.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
