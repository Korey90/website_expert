<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AutomationTrigger extends Model
{
    protected $fillable = [
        'key',
        'label',
        'group',
        'description',
        'variables',
        'is_system',
        'is_active',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_system' => 'boolean',
        'is_active' => 'boolean',
    ];

    // ── Cache helpers ─────────────────────────────────────────────────────────

    /**
     * Returns ['key' => 'label'] map for all active triggers, grouped/sorted.
     * Cached for 5 minutes and invalidated on save/delete.
     */
    public static function getOptions(): array
    {
        return Cache::remember('automation_triggers_options', 300, function () {
            return static::where('is_active', true)
                ->orderBy('group')
                ->orderBy('label')
                ->pluck('label', 'key')
                ->toArray();
        });
    }

    /**
     * Returns human-readable label for a trigger key.
     */
    public static function labelFor(?string $key): string
    {
        if ($key === null) {
            return '—';
        }
        $options = static::getOptions();
        return $options[$key] ?? $key;
    }

    /**
     * Returns available variables for a trigger key.
     */
    public static function variablesFor(string $key): array
    {
        return Cache::remember("automation_trigger_vars_{$key}", 300, function () use ($key) {
            return static::where('key', $key)->value('variables') ?? [];
        });
    }

    // ── Cache invalidation ────────────────────────────────────────────────────

    protected static function booted(): void
    {
        static::saved(fn () => static::clearCache());
        static::deleted(fn () => static::clearCache());
    }

    public static function clearCache(): void
    {
        Cache::forget('automation_triggers_options');
        // Per-key caches cleared by pattern not possible without tagging — flush all
        // In production with Redis, use Cache::tags(['automation'])->flush()
    }
}
