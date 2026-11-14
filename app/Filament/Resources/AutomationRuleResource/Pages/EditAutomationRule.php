<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAutomationRule extends EditRecord
{
    protected static string $resource = AutomationRuleResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.rules.edit_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
