<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcVatSubmission extends Model
{
    use BelongsToTenant;

    public const STATUS_PREPARED = 'prepared';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_OUTCOME_UNKNOWN = 'outcome_unknown';

    public const DECLARATION_VERSION = 'hmrc-vat-business-declaration-v1';

    protected $fillable = [
        'business_id', 'vat_return_id', 'hmrc_vat_obligation_id', 'environment',
        'status', 'snapshot_hash', 'payload_hash', 'declaration_version',
        'declared_at', 'mfa_type', 'mfa_verified_at', 'mfa_reference_hash',
        'submitted_by', 'submitted_at', 'processing_date', 'form_bundle_number',
        'payment_indicator', 'charge_reference', 'correlation_id', 'failure_code',
        'failure_status',
    ];

    protected $hidden = [
        'form_bundle_number', 'charge_reference', 'correlation_id',
        'mfa_reference_hash', 'failure_code',
    ];

    protected function casts(): array
    {
        return [
            'declared_at' => 'datetime',
            'mfa_verified_at' => 'datetime',
            'submitted_at' => 'datetime',
            'processing_date' => 'datetime',
            'form_bundle_number' => 'encrypted',
            'charge_reference' => 'encrypted',
            'failure_status' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function vatReturn(): BelongsTo
    {
        return $this->belongsTo(VatReturn::class);
    }

    public function obligation(): BelongsTo
    {
        return $this->belongsTo(HmrcVatObligation::class, 'hmrc_vat_obligation_id');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }
}
