<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use InvalidArgumentException;

class RecurringExpenseTemplate extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_PAUSED = 'paused';

    public const STATUS_COMPLETED = 'completed';

    public const FREQUENCY_WEEKLY = 'weekly';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_YEARLY = 'yearly';

    /** @return list<string> */
    public static function frequencies(): array
    {
        return [self::FREQUENCY_WEEKLY, self::FREQUENCY_MONTHLY, self::FREQUENCY_QUARTERLY, self::FREQUENCY_YEARLY];
    }

    /** @return list<string> */
    public static function statuses(): array
    {
        return [self::STATUS_ACTIVE, self::STATUS_PAUSED, self::STATUS_COMPLETED];
    }

    protected $fillable = [
        'business_id', 'supplier_id', 'client_id', 'project_id', 'created_by',
        'name', 'status', 'frequency', 'interval', 'start_date', 'end_date',
        'next_run_date', 'last_generated_at', 'currency', 'due_days',
        'payment_method', 'business_purpose', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'interval' => 'integer', 'due_days' => 'integer',
            'start_date' => 'date', 'end_date' => 'date', 'next_run_date' => 'date',
            'last_generated_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class)->withTrashed();
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class)->withTrashed();
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class)->withTrashed();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RecurringExpenseTemplateItem::class)->orderBy('order');
    }

    public function generatedExpenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function nextOccurrenceAfter(CarbonInterface $date): CarbonImmutable
    {
        $date = CarbonImmutable::instance($date);
        $interval = max(1, $this->interval);

        return match ($this->frequency) {
            self::FREQUENCY_WEEKLY => $date->addWeeks($interval),
            self::FREQUENCY_MONTHLY => $this->addAnchoredMonths($date, $interval),
            self::FREQUENCY_QUARTERLY => $this->addAnchoredMonths($date, 3 * $interval),
            self::FREQUENCY_YEARLY => $this->addAnchoredMonths($date, 12 * $interval),
            default => throw new InvalidArgumentException("Unsupported recurring expense frequency [{$this->frequency}]."),
        };
    }

    private function addAnchoredMonths(CarbonImmutable $date, int $months): CarbonImmutable
    {
        $targetMonth = $date->startOfMonth()->addMonths($months);
        $anchorDay = CarbonImmutable::parse($this->start_date)->day;

        return $targetMonth->day(min($anchorDay, $targetMonth->daysInMonth));
    }
}
