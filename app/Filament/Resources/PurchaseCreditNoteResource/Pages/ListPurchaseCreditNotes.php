<?php

namespace App\Filament\Resources\PurchaseCreditNoteResource\Pages;

use App\Filament\Resources\PurchaseCreditNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListPurchaseCreditNotes extends ListRecords
{
    protected static string $resource = PurchaseCreditNoteResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.procurement.purchase_credit_notes.list_subheading');
    }
}
