<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainContact extends Model
{
    use HasFactory;

    protected $fillable = [
        'domain_order_id',
        'type',
        'first_name',
        'last_name',
        'email',
        'phone',
        'organisation',
        'address_line1',
        'address_line2',
        'city',
        'county',
        'postcode',
        'country_code',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function domainOrder(): BelongsTo
    {
        return $this->belongsTo(DomainOrder::class);
    }

    // ── Helpers ────────────────────────────────────────────────────────────────

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getFullAddressAttribute(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->county,
            $this->postcode,
            $this->country_code,
        ])->filter()->implode(', ');
    }
}
