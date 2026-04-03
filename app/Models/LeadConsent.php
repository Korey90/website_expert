<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadConsent extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id', 'given', 'consent_text', 'consent_version',
        'collected_at', 'source_url', 'ip_hash', 'locale',
    ];

    protected $casts = [
        'given'        => 'boolean',
        'collected_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getAuditSummaryAttribute(): string
    {
        $version = $this->consent_version ?? 'v?';
        $date    = $this->collected_at?->toDateString() ?? 'unknown date';
        $from    = $this->source_url ?? 'unknown source';

        return "Consent {$version} given on {$date} from {$from}";
    }
}
