<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcSandboxEvidenceEvent extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id', 'environment', 'sequence', 'operation', 'outcome',
        'actor_id', 'occurred_at', 'correlation_id', 'summary',
        'previous_hash', 'event_hash',
    ];

    protected $hidden = ['correlation_id', 'summary'];

    protected function casts(): array
    {
        return [
            'sequence' => 'integer',
            'occurred_at' => 'datetime',
            'summary' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (): void {
            throw new DomainException('HMRC sandbox evidence events are immutable.');
        });
        static::deleting(function (): void {
            throw new DomainException('HMRC sandbox evidence events are immutable.');
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
