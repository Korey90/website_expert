<?php

namespace App\Filament\Resources\BankTransactionResource\Pages;

use App\Filament\Resources\BankTransactionResource;
use Filament\Resources\Pages\ListRecords;

class ListBankTransactions extends ListRecords
{
    protected static string $resource = BankTransactionResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.transactions.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
