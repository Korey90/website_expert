<?php

namespace App\Filament\Resources\RecurringExpenseTemplateResource\Pages;

use App\Filament\Resources\RecurringExpenseTemplateResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRecurringExpenseTemplates extends ListRecords
{
    protected static string $resource = RecurringExpenseTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
