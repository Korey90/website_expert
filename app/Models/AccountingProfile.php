<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountingProfile extends Model
{
    use BelongsToTenant, HasFactory;

    public const ENTITY_TYPES = [
        'limited_company',
        'sole_trader',
        'partnership',
    ];

    public const ACCOUNTING_BASES = [
        'accrual',
        'cash',
    ];

    protected $fillable = [
        'business_id',
        'entity_type',
        'legal_name',
        'company_number',
        'registered_address',
        'company_utr',
        'functional_currency',
        'accounting_basis',
        'financial_year_start',
        'financial_year_end',
        'vat_registered',
        'vat_number',
        'vat_scheme',
        'vat_effective_from',
        'vat_effective_to',
        'paye_reference',
        'accounts_office_reference',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_address' => 'array',
            'company_utr' => 'encrypted',
            'financial_year_start' => 'date',
            'financial_year_end' => 'date',
            'vat_registered' => 'boolean',
            'vat_effective_from' => 'date',
            'vat_effective_to' => 'date',
            'paye_reference' => 'encrypted',
            'accounts_office_reference' => 'encrypted',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
