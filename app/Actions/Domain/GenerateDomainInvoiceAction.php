<?php

namespace App\Actions\Domain;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;

class GenerateDomainInvoiceAction
{
    /**
     * Generate a draft Invoice for a domain order.
     * Idempotent — if an invoice already exists for this order, returns it.
     */
    public function execute(DomainOrder $order, ?int $createdBy = null): Invoice
    {
        $existing = Invoice::where('domain_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $number = $this->nextNumber();

        $invoice = Invoice::create([
            'number'          => $number,
            'client_id'       => $order->client_id,
            'domain_order_id' => $order->id,
            'created_by'      => $createdBy ?? $order->created_by,
            'status'          => 'draft',
            'currency'        => $order->currency ?? 'GBP',
            'vat_rate'        => 20.00,
            'subtotal'        => 0,
            'discount_amount' => 0,
            'vat_amount'      => 0,
            'total'           => 0,
            'amount_paid'     => 0,
            'amount_due'      => 0,
            'issue_date'      => now()->toDateString(),
            'due_date'        => now()->addDays(7)->toDateString(),
            'notes'           => ucfirst($order->action) . ' of ' . $order->full_domain,
        ]);

        $description = match ($order->action) {
            'register' => 'Domain registration',
            'transfer' => 'Domain transfer',
            'renew'    => 'Domain renewal',
            default    => 'Domain order',
        };

        $periodLabel = $order->years === 1
            ? '1 year'
            : $order->years . ' years';

        InvoiceItem::create([
            'invoice_id'  => $invoice->id,
            'description' => "{$description} — {$order->full_domain} ({$periodLabel})",
            'details'     => null,
            'quantity'    => 1.00,
            'unit_price'  => (float) $order->retail_price,
            'amount'      => (float) $order->retail_price,
            'order'       => 1,
        ]);

        $invoice->recalculate();
        $invoice->refresh();

        return $invoice;
    }

    private function nextNumber(): string
    {
        $year  = date('Y');
        $count = Invoice::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'INV-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
