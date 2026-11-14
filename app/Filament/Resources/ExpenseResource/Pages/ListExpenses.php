<?php

namespace App\Filament\Resources\ExpenseResource\Pages;

use App\Filament\Resources\ExpenseResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListExpenses extends ListRecords
{
    protected static string $resource = ExpenseResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.procurement.expenses.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
