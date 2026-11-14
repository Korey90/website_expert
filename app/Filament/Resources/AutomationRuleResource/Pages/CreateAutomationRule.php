<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAutomationRule extends CreateRecord
{
    protected static string $resource = AutomationRuleResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.automation.rules.create_subheading');
    }
}
