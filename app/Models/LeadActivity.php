<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class LeadActivity extends Model
{
    protected $fillable = [
        'lead_id',
        'user_id',
        'type',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // ── Relations ─────────────────────────────────────────────────────────

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Factory helper ────────────────────────────────────────────────────

    public static function log(
        int    $leadId,
        string $type,
        string $description,
        array  $metadata = [],
        ?int   $userId   = null,
    ): self {
        return static::create([
            'lead_id'     => $leadId,
            'user_id'     => $userId ?? Auth::id(),
            'type'        => $type,
            'description' => $description,
            'metadata'    => $metadata ?: null,
        ]);
    }

    // ── Icon / colour helpers used in views ───────────────────────────────

    public function getIconAttribute(): string
    {
        return match ($this->type) {
            'created'         => 'heroicon-m-plus-circle',
            'stage_moved'     => 'heroicon-m-arrows-right-left',
            'marked_won'      => 'heroicon-m-check-circle',
            'marked_lost'     => 'heroicon-m-x-circle',
            'email_sent'      => 'heroicon-m-envelope',
            'note_updated'    => 'heroicon-m-document-text',
            'project_created' => 'heroicon-m-folder-plus',
            'assigned'        => 'heroicon-m-user-plus',
            'deleted'         => 'heroicon-m-trash',
            'restored'        => 'heroicon-m-arrow-uturn-left',
            default           => 'heroicon-m-information-circle',
        };
    }

    public function getColorAttribute(): string
    {
        return match ($this->type) {
            'created'         => 'text-blue-500',
            'stage_moved'     => 'text-indigo-500',
            'marked_won'      => 'text-green-500',
            'marked_lost'     => 'text-red-500',
            'email_sent'      => 'text-sky-500',
            'note_updated'    => 'text-yellow-500',
            'project_created' => 'text-purple-500',
            'assigned'        => 'text-teal-500',
            'deleted'         => 'text-red-400',
            'restored'        => 'text-gray-400',
            default           => 'text-gray-400',
        };
    }

    public function getBgAttribute(): string
    {
        return match ($this->type) {
            'created'         => 'bg-blue-100 dark:bg-blue-900/30',
            'stage_moved'     => 'bg-indigo-100 dark:bg-indigo-900/30',
            'marked_won'      => 'bg-green-100 dark:bg-green-900/30',
            'marked_lost'     => 'bg-red-100 dark:bg-red-900/30',
            'email_sent'      => 'bg-sky-100 dark:bg-sky-900/30',
            'note_updated'    => 'bg-yellow-100 dark:bg-yellow-900/30',
            'project_created' => 'bg-purple-100 dark:bg-purple-900/30',
            'assigned'        => 'bg-teal-100 dark:bg-teal-900/30',
            default           => 'bg-gray-100 dark:bg-gray-800',
        };
    }
}
