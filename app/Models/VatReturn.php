<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VatReturn extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_LOCKED = 'locked';

    public const STATUS_SUPERSEDED = 'superseded';

    public const REVIEW_CONFIRMATION_VERSION = 'internal-review-v1';

    protected $fillable = [
        'business_id', 'number', 'status', 'period_from', 'period_to',
        'snapshot_version', 'snapshot', 'snapshot_hash', 'source_journal_count',
        'source_line_count', 'warning_count', 'created_by', 'reviewed_by',
        'reviewed_at', 'review_notes', 'review_confirmation_version', 'locked_by',
        'locked_at', 'lock_reason', 'superseded_by', 'superseded_at',
        'supersede_reason',
    ];

    protected function casts(): array
    {
        return [
            'period_from' => 'date',
            'period_to' => 'date',
            'snapshot_version' => 'integer',
            'snapshot' => 'array',
            'source_journal_count' => 'integer',
            'source_line_count' => 'integer',
            'warning_count' => 'integer',
            'reviewed_at' => 'datetime',
            'locked_at' => 'datetime',
            'superseded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (VatReturn $return): void {
            if (blank($return->status)) {
                $return->status = self::STATUS_DRAFT;
            }

            if ($return->status !== self::STATUS_DRAFT) {
                throw new DomainException(__('finance.vat_returns.errors.created_draft'));
            }
        });

        static::updating(function (VatReturn $return): void {
            if ($return->isDirty(self::snapshotFields())) {
                throw new DomainException(__('finance.vat_returns.errors.immutable'));
            }

            if ($return->isDirty('status')) {
                $from = (string) $return->getOriginal('status');
                $to = (string) $return->status;
                $allowed = match ($from) {
                    self::STATUS_DRAFT => [self::STATUS_REVIEWED, self::STATUS_SUPERSEDED],
                    self::STATUS_REVIEWED => [self::STATUS_LOCKED, self::STATUS_SUPERSEDED],
                    default => [],
                };

                if (! in_array($to, $allowed, true)) {
                    throw new DomainException(__('finance.vat_returns.errors.invalid_transition', ['from' => $from, 'to' => $to]));
                }
            }
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.vat_returns.errors.cannot_delete'));
        });
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_REVIEWED, self::STATUS_LOCKED, self::STATUS_SUPERSEDED];
    }

    /** @return list<string> */
    public static function activeStatuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_REVIEWED, self::STATUS_LOCKED];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function supersededBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'superseded_by');
    }

    public function hmrcSubmission(): HasOne
    {
        return $this->hasOne(HmrcVatSubmission::class);
    }

    /** @return list<string> */
    private static function snapshotFields(): array
    {
        return [
            'business_id', 'number', 'period_from', 'period_to', 'snapshot_version',
            'snapshot', 'snapshot_hash', 'source_journal_count', 'source_line_count',
            'warning_count', 'created_by',
        ];
    }
}
