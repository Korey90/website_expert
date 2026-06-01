<?php

namespace App\Actions\Domain;

use App\Models\DomainOrder;
use App\Models\Quote;
use App\Models\QuoteItem;

class GenerateDomainQuoteAction
{
    /**
     * Generate a draft Quote for a domain order.
     * Idempotent — if a quote already exists for this order, returns it.
     */
    public function execute(DomainOrder $order, ?int $createdBy = null): Quote
    {
        $existing = Quote::where('domain_order_id', $order->id)->first();
        if ($existing) {
            return $existing;
        }

        $quote = Quote::create([
            'number'          => $this->nextNumber(),
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
            'valid_until'     => now()->addDays(30)->toDateString(),
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

        QuoteItem::create([
            'quote_id'    => $quote->id,
            'description' => "{$description} — {$order->full_domain} ({$periodLabel})",
            'details'     => null,
            'quantity'    => 1.00,
            'unit_price'  => (float) $order->retail_price,
            'amount'      => (float) $order->retail_price,
            'order'       => 1,
        ]);

        $quote->recalculate();
        $quote->refresh();

        return $quote;
    }

    private function nextNumber(): string
    {
        $year  = date('Y');
        $count = Quote::withTrashed()->whereYear('created_at', $year)->count() + 1;

        return 'QUOT-' . $year . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
    }
}
