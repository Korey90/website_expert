<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcVatObligation extends Model
{
    use BelongsToTenant;

    public const STATUS_OPEN = 'open';

    public const STATUS_FULFILLED = 'fulfilled';

    protected $fillable = [
        'business_id',
        'environment',
        'period_key',
        'start_date',
        'end_date',
        'due_date',
        'status',
        'received_date',
        'first_seen_at',
        'last_seen_at',
        'status_changed_at',
        'last_synced_by',
        'last_correlation_id',
    ];

    protected $hidden = [
        'period_key',
        'last_correlation_id',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'due_date' => 'date',
            'received_date' => 'date',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'status_changed_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function lastSyncedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'last_synced_by');
    }

    public function scopeReturnViewable(Builder $query): Builder
    {
        return $query->where(function (Builder $query): void {
            $query->where($query->qualifyColumn('status'), self::STATUS_FULFILLED)
                ->orWhereExists(function ($subquery): void {
                    $subquery->selectRaw('1')
                        ->from('hmrc_vat_submissions')
                        ->whereColumn('hmrc_vat_submissions.hmrc_vat_obligation_id', 'hmrc_vat_obligations.id')
                        ->whereColumn('hmrc_vat_submissions.business_id', 'hmrc_vat_obligations.business_id')
                        ->whereColumn('hmrc_vat_submissions.environment', 'hmrc_vat_obligations.environment')
                        ->where('hmrc_vat_submissions.status', HmrcVatSubmission::STATUS_SUBMITTED);
                });
        });
    }
}
