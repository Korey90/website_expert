<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditNoteItem extends Model
{
    use BelongsToTenant, HasFactory;

    protected $fillable = [
        'business_id', 'credit_note_id', 'invoice_item_id', 'tax_code_id', 'description',
        'quantity', 'unit_price', 'supply_date', 'net_amount', 'net_amount_gbp',
        'vat_rate_snapshot', 'vat_amount', 'vat_amount_gbp', 'gross_amount', 'gross_amount_gbp', 'order',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2', 'unit_price' => 'decimal:2', 'supply_date' => 'date',
            'net_amount' => 'decimal:2', 'net_amount_gbp' => 'decimal:2',
            'vat_rate_snapshot' => 'decimal:2', 'vat_amount' => 'decimal:2',
            'vat_amount_gbp' => 'decimal:2', 'gross_amount' => 'decimal:2', 'gross_amount_gbp' => 'decimal:2',
        ];
    }

    public function creditNote(): BelongsTo
    {
        return $this->belongsTo(CreditNote::class);
    }

    public function invoiceItem(): BelongsTo
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function taxCode(): BelongsTo
    {
        return $this->belongsTo(TaxCode::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
