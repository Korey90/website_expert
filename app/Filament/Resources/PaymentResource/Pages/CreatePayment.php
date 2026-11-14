<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Actions\Finance\RecordInvoicePaymentAction;
use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    public function getSubheading(): ?string
    {
        return __('admin.migration.banking.payments.create_subheading');
    }

    /** @param array<string, mixed> $data */
    protected function handleRecordCreation(array $data): Model
    {
        $invoice = Invoice::findOrFail($data['invoice_id']);

        return app(RecordInvoicePaymentAction::class)->execute($invoice, auth()->user(), $data);
    }
}
