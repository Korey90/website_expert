<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingPeriod extends Model
{
    use BelongsToTenant, HasFactory;

    public const STATUS_OPEN = 'open';

    public const STATUS_CLOSING = 'closing';

    public const STATUS_CLOSED = 'closed';

    public const STATUS_LOCKED = 'locked';

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_OPEN, self::STATUS_CLOSING, self::STATUS_CLOSED, self::STATUS_LOCKED];
    }

    protected $fillable = [
        'business_id', 'name', 'financial_year', 'period_number', 'start_date', 'end_date', 'status',
        'created_by', 'closing_started_by', 'closing_started_at', 'closed_by', 'closed_at', 'close_notes',
        'locked_by', 'locked_at', 'lock_reason', 'reopened_by', 'reopened_at', 'reopen_reason', 'transition_history',
    ];

    protected function casts(): array
    {
        return [
            'financial_year' => 'integer',
            'period_number' => 'integer',
            'start_date' => 'date',
            'end_date' => 'date',
            'closing_started_at' => 'datetime',
            'closed_at' => 'datetime',
            'locked_at' => 'datetime',
            'reopened_at' => 'datetime',
            'transition_history' => 'array',
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

    public function lockedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'locked_by');
    }

    public function journalEntries(): HasMany
    {
        return $this->hasMany(JournalEntry::class);
    }

    public function scopeContaining(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query->whereDate('start_date', '<=', $date)->whereDate('end_date', '>=', $date);
    }

    public function isWritable(): bool
    {
        return in_array($this->status, [self::STATUS_OPEN, self::STATUS_CLOSING], true);
    }
}
