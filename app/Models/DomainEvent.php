<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DomainEvent extends Model
{
    use HasFactory;

    public const UPDATED_AT = null; // domain_events has only created_at

    protected $fillable = [
        'domain_id',
        'domain_order_id',
        'user_id',
        'type',
        'description',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];

    // ── Relationships ──────────────────────────────────────────────────────────

    public function domain(): BelongsTo
    {
        return $this->belongsTo(Domain::class)->withTrashed();
    }

    public function domainOrder(): BelongsTo
    {
        return $this->belongsTo(DomainOrder::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Factory method ─────────────────────────────────────────────────────────

    public static function log(
        ?int $domainId,
        ?int $domainOrderId,
        string $type,
        ?string $description = null,
        ?array $payload = null,
        ?int $userId = null,
    ): self {
        return static::create([
            'domain_id'       => $domainId,
            'domain_order_id' => $domainOrderId,
            'user_id'         => $userId ?? auth()->id(),
            'type'            => $type,
            'description'     => $description,
            'payload'         => $payload,
        ]);
    }
}
