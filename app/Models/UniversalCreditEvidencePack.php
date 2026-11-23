<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversalCreditEvidencePack extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_EXPIRED = 'expired';

    protected $fillable = [
        'business_id', 'assessment_period_id', 'version', 'status', 'snapshot_hash',
        'locale', 'disk', 'path', 'filename', 'zip_sha256', 'bytes',
        'included_count', 'excluded_count', 'attempt_count', 'last_error_code',
        'requested_by', 'started_at', 'generated_at', 'expires_at', 'failed_at',
        'file_deleted_at',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'bytes' => 'integer',
            'included_count' => 'integer',
            'excluded_count' => 'integer',
            'attempt_count' => 'integer',
            'started_at' => 'datetime',
            'generated_at' => 'datetime',
            'expires_at' => 'datetime',
            'failed_at' => 'datetime',
            'file_deleted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $pack): void {
            $pack->status ??= self::STATUS_PENDING;

            if ($pack->status !== self::STATUS_PENDING
                || $pack->version < 1
                || preg_match('/^[a-f0-9]{64}$/', (string) $pack->snapshot_hash) !== 1) {
                throw new DomainException(__('finance.universal_credit.pack.errors.invalid_record'));
            }
        });

        static::updating(function (self $pack): void {
            if ($pack->isDirty([
                'business_id', 'assessment_period_id', 'version', 'snapshot_hash', 'locale', 'requested_by',
            ])) {
                throw new DomainException(__('finance.universal_credit.pack.errors.foundation_immutable'));
            }

            $originalStatus = (string) $pack->getOriginal('status');
            if ($originalStatus === self::STATUS_EXPIRED) {
                throw new DomainException(__('finance.universal_credit.pack.errors.foundation_immutable'));
            }

            if ($originalStatus === self::STATUS_COMPLETED) {
                $allowedDirty = ['status', 'file_deleted_at', 'updated_at'];
                if ($pack->status !== self::STATUS_EXPIRED
                    || array_diff(array_keys($pack->getDirty()), $allowedDirty) !== []) {
                    throw new DomainException(__('finance.universal_credit.pack.errors.foundation_immutable'));
                }
            }

            if (! $pack->isDirty('status')) {
                return;
            }

            $allowed = match ($originalStatus) {
                self::STATUS_PENDING => [self::STATUS_PROCESSING],
                self::STATUS_PROCESSING => [self::STATUS_COMPLETED, self::STATUS_FAILED],
                self::STATUS_FAILED => [self::STATUS_PENDING],
                self::STATUS_COMPLETED => [self::STATUS_EXPIRED],
                default => [],
            };

            if (! in_array($pack->status, $allowed, true)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.invalid_transition'));
            }

            if ($pack->status === self::STATUS_COMPLETED
                && (blank($pack->disk) || blank($pack->path) || blank($pack->filename)
                    || preg_match('/^[a-f0-9]{64}$/', (string) $pack->zip_sha256) !== 1
                    || $pack->bytes === null || $pack->generated_at === null || $pack->expires_at === null)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.incomplete_build'));
            }

            if ($pack->status === self::STATUS_FAILED
                && (blank($pack->last_error_code) || $pack->failed_at === null)) {
                throw new DomainException(__('finance.universal_credit.pack.errors.incomplete_failure'));
            }

            if ($pack->status === self::STATUS_EXPIRED && $pack->file_deleted_at === null) {
                throw new DomainException(__('finance.universal_credit.pack.errors.incomplete_expiry'));
            }
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.pack.errors.cannot_delete'));
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assessmentPeriod(): BelongsTo
    {
        return $this->belongsTo(UniversalCreditAssessmentPeriod::class, 'assessment_period_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(UniversalCreditEvidencePackItem::class, 'evidence_pack_id');
    }
}
