<?php

namespace App\Filament\Resources\LedgerAccountResource\Pages;

use App\Filament\Resources\LedgerAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
