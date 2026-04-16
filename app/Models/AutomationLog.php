<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AutomationLog extends Model
{
    protected $fillable = [
        'automation_rule_id',
        'trigger_event',
        'context',
        'actions_executed',
        'lead_id',
        'client_id',
        'status',
        'source',
        'executed_at',
    ];

    protected $casts = [
        'context'          => 'array',
        'actions_executed' => 'array',
        'executed_at'      => 'datetime',
    ];

    // ── Relations ────────────────────────────────────────────────────────────

    public function automationRule(): BelongsTo
    {
        return $this->belongsTo(AutomationRule::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeOlderThanDays(Builder $query, int $days): Builder
    {
        return $query->where('executed_at', '<', now()->subDays($days));
    }

    public function scopeRealRuns(Builder $query): Builder
    {
        return $query->where('source', 'automation');
    }

    public function scopeTestRuns(Builder $query): Builder
    {
        return $query->where('source', 'test');
    }
}
