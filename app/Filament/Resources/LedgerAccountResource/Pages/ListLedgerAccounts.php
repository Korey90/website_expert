<?php

namespace App\Filament\Resources\LedgerAccountResource\Pages;

use App\Filament\Resources\LedgerAccountResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListLedgerAccounts extends ListRecords
{
    protected static string $resource = LedgerAccountResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.ledger_accounts.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
