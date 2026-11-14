<?php

namespace App\Filament\Resources\CreditNoteResource\Pages;

use App\Filament\Resources\CreditNoteResource;
use Filament\Resources\Pages\ListRecords;

class ListCreditNotes extends ListRecords
{
    protected static string $resource = CreditNoteResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.sales_documents.credit_notes.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [];
    }
}
