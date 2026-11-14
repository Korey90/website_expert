<?php

namespace App\Filament\Resources\QuoteResource\Pages;

use App\Actions\Finance\GenerateDocumentNumberAction;
use App\Filament\Resources\QuoteResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateQuote extends CreateRecord
{
    protected static string $resource = QuoteResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.sales_documents.quotes.create_subheading');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = Auth::id();
        $data['number'] = app(GenerateDocumentNumberAction::class)->execute(currentBusiness()->id, 'quote');

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->record->recalculate();
    }
}
