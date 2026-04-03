<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeadSource extends Model
{
    use HasFactory;

    public const UPDATED_AT = null;

    public $timestamps = false;

    protected $fillable = [
        'lead_id', 'business_id', 'type', 'landing_page_id',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term',
        'referrer_url', 'page_url',
        'ip_address', 'ip_hash', 'user_agent', 'device_type', 'country_code',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public const TYPES = [
        'landing_page',
        'contact_form',
        'calculator',
        'api',
        'manual',
        'import',
        'referral',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function landingPage(): BelongsTo
    {
        return $this->belongsTo(LandingPage::class);
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    public function scopeForBusiness($query, Business $business)
    {
        return $query->where('business_id', $business->id);
    }

    public function scopeLastDays($query, int $days)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // -----------------------------------------------------------------
    // Accessors
    // -----------------------------------------------------------------

    public function getChannelAttribute(): string
    {
        $medium = $this->utm_medium;
        $source = $this->utm_source;

        if ($medium === 'cpc') return 'Paid Search';
        if ($medium === 'email') return 'Email';
        if ($medium === 'social') return 'Social';
        if ($source === 'google') return 'Organic Search';
        if ($source === 'facebook' || $source === 'instagram') return 'Social';
        if (! empty($source)) return ucfirst($source);

        return 'Direct';
    }

    public function getDisplayTypeAttribute(): string
    {
        return match ($this->type) {
            'landing_page'  => 'Landing Page',
            'contact_form'  => 'Contact Form',
            'calculator'    => 'Calculator',
            'api'           => 'API',
            'manual'        => 'Manual',
            'import'        => 'CSV Import',
            'referral'      => 'Referral',
            default         => ucfirst($this->type),
        };
    }

    // -----------------------------------------------------------------
    // Analytics helpers (used by widgets)
    // -----------------------------------------------------------------

    public static function countByTypeForBusiness(Business $business, int $days = 7): array
    {
        return static::where('business_id', $business->id)
            ->where('created_at', '>=', now()->subDays($days))
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type')
            ->toArray();
    }
}
