<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class CalendarEvent extends Model
{
    use HasFactory, BelongsToTenant;

    protected $fillable = [
        'business_id', 'user_id',
        'title', 'description',
        'starts_at', 'ends_at', 'all_day',
        'type', 'status', 'color',
        'related_type', 'related_id',
        'google_event_id', 'google_synced_at',
    ];

    protected $casts = [
        'starts_at'       => 'datetime',
        'ends_at'         => 'datetime',
        'all_day'         => 'boolean',
        'google_synced_at'=> 'datetime',
    ];

    // ── Constants ────────────────────────────────────────────────────────

    public const TYPES = ['meeting', 'call', 'deadline', 'reminder', 'task'];

    public const TYPE_COLORS = [
        'meeting'  => '#3b82f6', // blue
        'call'     => '#10b981', // green
        'deadline' => '#ef4444', // red
        'reminder' => '#f59e0b', // amber
        'task'     => '#8b5cf6', // purple
    ];

    public const STATUSES = ['scheduled', 'done', 'cancelled'];

    // ── Relationships ─────────────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function related(): MorphTo
    {
        return $this->morphTo();
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    public function getEffectiveColor(): string
    {
        return $this->color ?? self::TYPE_COLORS[$this->type] ?? '#6b7280';
    }

    public function isSyncedToGoogle(): bool
    {
        return $this->google_event_id !== null;
    }

    /**
     * Convert to FullCalendar event object.
     */
    public function toCalendarArray(): array
    {
        return [
            'id'              => $this->id,
            'title'           => $this->title,
            'start'           => $this->starts_at->toIso8601String(),
            'end'             => $this->ends_at?->toIso8601String(),
            'allDay'          => $this->all_day,
            'color'           => $this->getEffectiveColor(),
            'extendedProps'   => [
                'type'        => $this->type,
                'status'      => $this->status,
                'description' => $this->description,
                'editUrl'     => route('filament.admin.resources.calendar-events.edit', $this->id),
                'synced'      => $this->isSyncedToGoogle(),
            ],
        ];
    }
}
