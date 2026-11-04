<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'invoice_id', 'description', 'details', 'tax_code_id', 'supply_date',
        'quantity', 'unit_price', 'amount', 'net_amount', 'vat_rate_snapshot',
        'net_amount_gbp', 'vat_amount', 'vat_amount_gbp', 'gross_amount', 'gross_amount_gbp', 'order',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'net_amount_gbp' => 'decimal:2',
        'vat_rate_snapshot' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'vat_amount_gbp' => 'decimal:2',
        'gross_amount' => 'decimal:2',
        'gross_amount_gbp' => 'decimal:2',
        'supply_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saving(function (InvoiceItem $item) {
            $item->amount = round($item->quantity * $item->unit_price, 2);

            if ($item->tax_code_id && ($item->vat_rate_snapshot === null || $item->isDirty('tax_code_id'))) {
                $item->vat_rate_snapshot = TaxCode::withoutGlobalScopes()->find($item->tax_code_id)?->rate;
            }
        });

        static::creating(function (InvoiceItem $item): void {
            if (empty($item->business_id) && $item->invoice_id) {
                $item->business_id = Invoice::withoutGlobalScopes()->find($item->invoice_id)?->business_id;
            }

            $item->assertInvoiceIsUnposted();
        });

        static::updating(function (InvoiceItem $item): void {
            $item->assertInvoiceIsUnposted();
        });

        static::deleting(function (InvoiceItem $item): void {
            $item->assertInvoiceIsUnposted();
        });
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function creditNoteItems(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class);
    }

    private function assertInvoiceIsUnposted(): void
    {
        if (! $this->invoice_id) {
            return;
        }

        $invoice = Invoice::withoutGlobalScopes()->find($this->invoice_id);
        if ($invoice?->journalEntries()->exists()) {
            throw new DomainException('Posted invoice items are immutable and require a credit note.');
        }
    }
}
