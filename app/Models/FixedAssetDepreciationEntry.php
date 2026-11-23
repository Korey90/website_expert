<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FixedAssetDepreciationEntry extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_PLANNED = 'planned';

    public const STATUS_POSTED = 'posted';

    public const STATUS_REVERSED = 'reversed';

    protected $fillable = [
        'business_id', 'fixed_asset_id', 'period_start', 'period_end', 'posting_date',
        'status', 'method_snapshot', 'rate_percent_snapshot', 'useful_life_months_snapshot',
        'residual_value_gbp', 'opening_cost_gbp', 'opening_accumulated_depreciation_gbp',
        'opening_net_book_value_gbp', 'amount_gbp', 'closing_accumulated_depreciation_gbp',
        'closing_net_book_value_gbp', 'journal_entry_id', 'reversal_journal_entry_id',
        'created_by', 'posted_by', 'reversed_by', 'posted_at', 'reversed_at', 'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'posting_date' => 'date',
            'rate_percent_snapshot' => 'decimal:2',
            'useful_life_months_snapshot' => 'integer',
            'residual_value_gbp' => 'decimal:2',
            'opening_cost_gbp' => 'decimal:2',
            'opening_accumulated_depreciation_gbp' => 'decimal:2',
            'opening_net_book_value_gbp' => 'decimal:2',
            'amount_gbp' => 'decimal:2',
            'closing_accumulated_depreciation_gbp' => 'decimal:2',
            'closing_net_book_value_gbp' => 'decimal:2',
            'posted_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (FixedAssetDepreciationEntry $entry): void {
            if ($entry->status !== self::STATUS_PLANNED) {
                throw new DomainException('A depreciation entry must be created as planned.');
            }
        });

        static::updating(function (FixedAssetDepreciationEntry $entry): void {
            if ($entry->getOriginal('status') !== self::STATUS_PLANNED
                || $entry->status !== self::STATUS_POSTED) {
                throw new DomainException('A posted depreciation entry is immutable and must be reversed.');
            }
        });

        static::deleting(function (FixedAssetDepreciationEntry $entry): void {
            if ($entry->status !== self::STATUS_PLANNED) {
                throw new DomainException('Only a planned depreciation entry can be replaced.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function fixedAsset(): BelongsTo
    {
        return $this->belongsTo(FixedAsset::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function reversalJournalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'reversal_journal_entry_id');
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
