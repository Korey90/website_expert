<?php

namespace App\Filament\Resources\SalesOfferTemplateResource\Pages;

use App\Filament\Resources\SalesOfferTemplateResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;

class EditSalesOfferTemplate extends EditRecord
{
    protected static string $resource = SalesOfferTemplateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('preview')
                ->label('Preview')
                ->icon('heroicon-o-eye')
                ->color('gray')
                ->modalHeading(fn () => $this->data['title'] ?? 'Template Preview')
                ->modalContent(fn () => view('filament.modals.sales-offer-template-preview', [
                    'body' => $this->data['body'] ?? '',
                ]))
                ->modalSubmitAction(false)
                ->modalCancelActionLabel('Close')
                ->modalWidth('4xl'),

            DeleteAction::make(),
        ];
    }
}
