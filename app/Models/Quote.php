<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number', 'client_id', 'lead_id', 'created_by',
        'status', 'currency', 'subtotal', 'discount_amount',
        'vat_rate', 'vat_amount', 'total', 'notes', 'terms',
        'valid_until', 'sent_at', 'accepted_at', 'rejected_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_rate'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'valid_until'     => 'date',
        'sent_at'         => 'datetime',
        'accepted_at'     => 'datetime',
        'rejected_at'     => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(QuoteItem::class)->orderBy('order');
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('amount');
        $vat = round(($subtotal - $this->discount_amount) * ($this->vat_rate / 100), 2);
        $this->update([
            'subtotal'   => $subtotal,
            'vat_amount' => $vat,
            'total'      => $subtotal - $this->discount_amount + $vat,
        ]);
    }
}
