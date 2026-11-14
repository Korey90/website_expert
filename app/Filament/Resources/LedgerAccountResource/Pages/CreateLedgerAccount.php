<?php

namespace App\Filament\Resources\LedgerAccountResource\Pages;

use App\Filament\Resources\LedgerAccountResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLedgerAccount extends CreateRecord
{
    protected static string $resource = LedgerAccountResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.accounting.ledger_accounts.create_subheading');
    }
}
