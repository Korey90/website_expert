<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class CreditNote extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ISSUED = 'issued';

    public const STATUS_PARTIALLY_ALLOCATED = 'partially_allocated';

    public const STATUS_ALLOCATED = 'allocated';

    public const STATUS_VOID = 'void';

    protected $fillable = [
        'business_id', 'number', 'invoice_id', 'client_id', 'created_by', 'issued_by', 'allocated_by', 'voided_by',
        'status', 'currency', 'reason', 'issue_date', 'tax_point',
        'gbp_exchange_rate', 'exchange_rate_source', 'exchange_rate_date',
        'subtotal', 'vat_amount', 'total', 'subtotal_gbp', 'vat_amount_gbp', 'total_gbp',
        'allocated_amount', 'issued_at', 'allocated_at', 'voided_at',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date', 'tax_point' => 'date', 'exchange_rate_date' => 'date',
            'gbp_exchange_rate' => 'decimal:8', 'subtotal' => 'decimal:2', 'vat_amount' => 'decimal:2',
            'total' => 'decimal:2', 'subtotal_gbp' => 'decimal:2', 'vat_amount_gbp' => 'decimal:2',
            'total_gbp' => 'decimal:2', 'allocated_amount' => 'decimal:2',
            'issued_at' => 'datetime', 'allocated_at' => 'datetime', 'voided_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function allocatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'allocated_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(CreditNoteItem::class)->orderBy('order');
    }
}
