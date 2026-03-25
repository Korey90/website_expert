<?php

namespace App\Filament\Resources\PaymentResource\Pages;

use App\Filament\Resources\PaymentResource;
use App\Models\Invoice;
use Filament\Resources\Pages\CreateRecord;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected function afterCreate(): void
    {
        // Recalculate invoice totals when a manual payment is added
        $invoice = Invoice::find($this->record->invoice_id);
        $invoice?->recalculate();

        if ($invoice && $invoice->fresh()->amount_due <= 0) {
            $invoice->update(['status' => 'paid', 'paid_at' => now()]);
        }
    }
}
