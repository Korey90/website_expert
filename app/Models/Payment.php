<?php

namespace App\Models;

use App\Models\Concerns\DefaultsCurrency;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use DefaultsCurrency, HasFactory;

    protected $fillable = [
        'invoice_id', 'amount', 'currency', 'method', 'status',
        'stripe_payment_intent_id', 'reference', 'notes', 'paid_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class)->withTrashed();
    }
}
