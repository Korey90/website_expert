<?php

namespace App\Filament\Resources\LedgerAccountResource\Pages;

use App\Filament\Resources\LedgerAccountResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditLedgerAccount extends EditRecord
{
    protected static string $resource = LedgerAccountResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
