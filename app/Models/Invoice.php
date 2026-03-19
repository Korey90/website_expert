<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'number', 'client_id', 'project_id', 'quote_id', 'created_by',
        'status', 'currency', 'subtotal', 'discount_amount',
        'vat_rate', 'vat_amount', 'total', 'amount_paid', 'amount_due',
        'issue_date', 'due_date', 'notes', 'terms',
        'stripe_payment_link', 'sent_at', 'paid_at',
    ];

    protected $casts = [
        'subtotal'        => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_rate'        => 'decimal:2',
        'vat_amount'      => 'decimal:2',
        'total'           => 'decimal:2',
        'amount_paid'     => 'decimal:2',
        'amount_due'      => 'decimal:2',
        'issue_date'      => 'date',
        'due_date'        => 'date',
        'sent_at'         => 'datetime',
        'paid_at'         => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class)->orderBy('order');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isOverdue(): bool
    {
        return $this->status !== 'paid'
            && $this->status !== 'cancelled'
            && $this->due_date->isPast();
    }

    public function recalculate(): void
    {
        $subtotal = $this->items()->sum('amount');
        $vat = round(($subtotal - $this->discount_amount) * ($this->vat_rate / 100), 2);
        $total = $subtotal - $this->discount_amount + $vat;
        $amountPaid = $this->payments()->where('status', 'completed')->sum('amount');
        $this->update([
            'subtotal'    => $subtotal,
            'vat_amount'  => $vat,
            'total'       => $total,
            'amount_paid' => $amountPaid,
            'amount_due'  => max(0, $total - $amountPaid),
        ]);
    }
}
