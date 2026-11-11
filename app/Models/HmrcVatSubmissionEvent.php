<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcVatSubmissionEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id', 'hmrc_vat_submission_id', 'sequence', 'event_type',
        'status', 'actor_id', 'occurred_at', 'metadata', 'previous_hash',
        'event_hash',
    ];

    protected $hidden = ['metadata'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'occurred_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new DomainException(__('finance.vat_returns.errors.audit_immutable'));
        });
        static::deleting(function (): void {
            throw new DomainException(__('finance.vat_returns.errors.audit_immutable'));
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function submission(): BelongsTo
    {
        return $this->belongsTo(HmrcVatSubmission::class, 'hmrc_vat_submission_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
