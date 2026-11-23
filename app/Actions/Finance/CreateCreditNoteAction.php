<?php

namespace App\Actions\Finance;

use App\Models\CreditNote;
use App\Models\CreditNoteItem;
use App\Models\FixedAssetTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateCreditNoteAction
{
    public function __construct(private readonly GenerateDocumentNumberAction $generateDocumentNumber) {}

    /**
     * @param  array{reason: string, issue_date?: string, tax_point?: string, items: list<array{invoice_item_id: int, quantity: float|int|string}>}  $data
     */
    public function execute(Invoice $invoice, User $actor, array $data): CreditNote
    {
        return DB::transaction(function () use ($invoice, $actor, $data): CreditNote {
            $lockedInvoice = Invoice::withoutGlobalScopes()->lockForUpdate()->findOrFail($invoice->id);

            if (! in_array($lockedInvoice->status, ['sent', 'partially_paid', 'paid', 'overdue'], true)) {
                throw new DomainException('Credit notes can only be created for an issued invoice.');
            }

            if (blank($data['reason'] ?? null) || empty($data['items'])) {
                throw new DomainException('A reason and at least one credit note item are required.');
            }

            $issueDate = $data['issue_date'] ?? now()->toDateString();
            $creditNote = CreditNote::withoutGlobalScopes()->create([
                'business_id' => $lockedInvoice->business_id,
                'number' => $this->generateDocumentNumber->execute(
                    $lockedInvoice->business_id,
                    'credit_note',
                    CarbonImmutable::parse($issueDate),
                ),
                'invoice_id' => $lockedInvoice->id,
                'client_id' => $lockedInvoice->client_id,
                'created_by' => $actor->id,
                'status' => CreditNote::STATUS_DRAFT,
                'currency' => $lockedInvoice->currency,
                'reason' => trim($data['reason']),
                'issue_date' => $issueDate,
                'tax_point' => $data['tax_point'] ?? $lockedInvoice->tax_point ?? $lockedInvoice->issue_date,
                'gbp_exchange_rate' => $lockedInvoice->gbp_exchange_rate,
                'exchange_rate_source' => $lockedInvoice->exchange_rate_source,
                'exchange_rate_date' => $lockedInvoice->exchange_rate_date,
            ]);

            $seenItems = [];

            foreach ($data['items'] as $order => $requestedItem) {
                $invoiceItemId = (int) $requestedItem['invoice_item_id'];

                if (isset($seenItems[$invoiceItemId])) {
                    throw new DomainException('An invoice item may only appear once on a credit note.');
                }

                $seenItems[$invoiceItemId] = true;
                $invoiceItem = InvoiceItem::withoutGlobalScopes()
                    ->where('invoice_id', $lockedInvoice->id)
                    ->lockForUpdate()
                    ->findOrFail($invoiceItemId);

                if (FixedAssetTransaction::withoutGlobalScopes()
                    ->where('invoice_item_id', $invoiceItem->id)
                    ->where('status', FixedAssetTransaction::STATUS_POSTED)
                    ->exists()) {
                    throw new DomainException('Reverse the fixed asset disposal before crediting this invoice item.');
                }
                $quantity = round((float) $requestedItem['quantity'], 2);
                $originalQuantity = (float) $invoiceItem->quantity;
                $alreadyCredited = (float) DB::table('credit_note_items')
                    ->join('credit_notes', 'credit_notes.id', '=', 'credit_note_items.credit_note_id')
                    ->where('credit_note_items.invoice_item_id', $invoiceItem->id)
                    ->where('credit_notes.status', '!=', CreditNote::STATUS_VOID)
                    ->where('credit_notes.id', '!=', $creditNote->id)
                    ->sum('credit_note_items.quantity');

                if ($quantity <= 0 || $quantity > round($originalQuantity - $alreadyCredited, 2)) {
                    throw new DomainException('Credited quantity exceeds the remaining invoice item quantity.');
                }

                if ($invoiceItem->net_amount === null || $invoiceItem->vat_amount === null || $invoiceItem->gross_amount === null) {
                    throw new DomainException('The invoice must be recalculated before a credit note is created.');
                }

                $ratio = $quantity / $originalQuantity;

                CreditNoteItem::withoutGlobalScopes()->create([
                    'business_id' => $lockedInvoice->business_id,
                    'credit_note_id' => $creditNote->id,
                    'invoice_item_id' => $invoiceItem->id,
                    'tax_code_id' => $invoiceItem->tax_code_id,
                    'description' => $invoiceItem->description,
                    'quantity' => $quantity,
                    'unit_price' => $invoiceItem->unit_price,
                    'supply_date' => $invoiceItem->supply_date,
                    'net_amount' => $this->proportion($invoiceItem->net_amount, $ratio),
                    'net_amount_gbp' => $this->proportion($invoiceItem->net_amount_gbp, $ratio),
                    'vat_rate_snapshot' => $invoiceItem->vat_rate_snapshot,
                    'vat_amount' => $this->proportion($invoiceItem->vat_amount, $ratio),
                    'vat_amount_gbp' => $this->proportion($invoiceItem->vat_amount_gbp, $ratio),
                    'gross_amount' => $this->proportion($invoiceItem->gross_amount, $ratio),
                    'gross_amount_gbp' => $this->proportion($invoiceItem->gross_amount_gbp, $ratio),
                    'order' => $order,
                ]);
            }

            $items = CreditNoteItem::withoutGlobalScopes()->where('credit_note_id', $creditNote->id)->get();
            $creditNote->update([
                'subtotal' => $items->sum('net_amount'),
                'vat_amount' => $items->sum('vat_amount'),
                'total' => $items->sum('gross_amount'),
                'subtotal_gbp' => $this->sumNullable($items->pluck('net_amount_gbp')->all()),
                'vat_amount_gbp' => $this->sumNullable($items->pluck('vat_amount_gbp')->all()),
                'total_gbp' => $this->sumNullable($items->pluck('gross_amount_gbp')->all()),
            ]);

            return $creditNote->fresh(['items']);
        });
    }

    private function proportion(string|float|int|null $amount, float $ratio): ?float
    {
        return $amount !== null ? round((float) $amount * $ratio, 2) : null;
    }

    /** @param  list<string|float|int|null>  $amounts */
    private function sumNullable(array $amounts): ?float
    {
        if (collect($amounts)->contains(null)) {
            return null;
        }

        return round((float) array_sum($amounts), 2);
    }
}
