<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class JournalEntry extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'business_id', 'accounting_period_id', 'reversal_of_id', 'entry_date', 'entry_number',
        'source_type', 'source_id', 'status', 'currency', 'description', 'idempotency_key',
        'source_snapshot_hash', 'created_by', 'posted_by', 'posted_at', 'reversed_by',
        'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (JournalEntry $entry): void {
            if (blank($entry->status)) {
                $entry->status = self::STATUS_DRAFT;
            } elseif ($entry->status !== self::STATUS_DRAFT) {
                throw new DomainException('A journal entry must be created as a draft and posted atomically.');
            }

            $period = AccountingPeriod::withoutGlobalScopes()->find($entry->accounting_period_id);

            if (! $period || $period->business_id !== $entry->business_id) {
                throw new DomainException('The accounting period must belong to the journal business.');
            }

            if (! $period->start_date->lte($entry->entry_date) || ! $period->end_date->gte($entry->entry_date)) {
                throw new DomainException('The journal date must fall within its accounting period.');
            }

            if ($entry->reversal_of_id !== null) {
                $original = self::withoutGlobalScopes()->find($entry->reversal_of_id);

                if (! $original || $original->business_id !== $entry->business_id) {
                    throw new DomainException('The reversed journal must belong to the same business.');
                }

                if ($original->status !== self::STATUS_POSTED || $original->reversal_of_id !== null) {
                    throw new DomainException('Only an original posted journal entry can be reversed.');
                }
            }
        });

        static::updating(function (JournalEntry $entry): void {
            if (in_array($entry->getOriginal('status'), [self::STATUS_POSTED, self::STATUS_REVERSED], true)) {
                throw new DomainException('A posted journal entry is immutable and must be reversed.');
            }
        });

        static::deleting(function (JournalEntry $entry): void {
            if ($entry->status !== self::STATUS_DRAFT) {
                throw new DomainException('A posted journal entry cannot be deleted.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function accountingPeriod(): BelongsTo
    {
        return $this->belongsTo(AccountingPeriod::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class)->orderBy('line_number');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }

    public function reversal(): HasOne
    {
        return $this->hasOne(self::class, 'reversal_of_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }
}
