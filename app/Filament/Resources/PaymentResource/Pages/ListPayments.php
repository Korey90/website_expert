<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPayments extends ListRecords
{
    protected static string $resource = PaymentResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.payments.list_subheading');
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('Add manual payment'),
        ];
    }
}
