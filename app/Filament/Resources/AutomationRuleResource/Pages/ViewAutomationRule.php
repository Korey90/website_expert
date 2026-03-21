<?php

namespace App\Filament\Resources\AutomationRuleResource\Pages;

use App\Filament\Resources\AutomationRuleResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewAutomationRule extends ViewRecord
{
    protected static string $resource = AutomationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
