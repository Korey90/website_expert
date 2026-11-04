<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Supplier extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'business_id', 'name', 'trading_name', 'company_number', 'vat_number',
        'contact_name', 'contact_email', 'contact_phone',
        'address_line1', 'address_line2', 'city', 'county', 'postcode', 'country',
        'default_currency', 'default_ledger_account_id', 'payment_terms_days', 'status', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'payment_terms_days' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function defaultLedgerAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'default_ledger_account_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function purchaseCreditNotes(): HasMany
    {
        return $this->hasMany(PurchaseCreditNote::class);
    }
}
