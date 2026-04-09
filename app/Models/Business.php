<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
class Business extends Model
{
    use HasFactory, HasUlids, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'locale',
        'timezone',
        'logo_path',
        'primary_color',
        'plan',
        'is_active',
        'trial_ends_at',
        'stripe_customer_id',
        'stripe_subscription_id',
        'stripe_subscription_status',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'is_active'      => 'boolean',
            'trial_ends_at'  => 'datetime',
            'settings'       => 'array',
        ];
    }

    // -------------------------------------------------------------------------
    // Relations
    // -------------------------------------------------------------------------

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'business_users')
            ->withPivot(['role', 'is_active', 'joined_at', 'invited_by'])
            ->withTimestamps();
    }

    public function members(): HasMany
    {
        return $this->hasMany(BusinessUser::class);
    }

    public function profile(): HasOne
    {
        return $this->hasOne(BusinessProfile::class);
    }

    public function landingPages(): HasMany
    {
        return $this->hasMany(LandingPage::class);
    }

    // -------------------------------------------------------------------------
    // Scopes
    // -------------------------------------------------------------------------

    public function scopeActive($query): void
    {
        $query->where('is_active', true);
    }

    // -------------------------------------------------------------------------
    // Accessors
    // -------------------------------------------------------------------------

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo_path
            ? Storage::disk('public')->url($this->logo_path)
            : null;
    }

    public function getIsOnTrialAttribute(): bool
    {
        return $this->trial_ends_at !== null && $this->trial_ends_at->isFuture();
    }

    // -------------------------------------------------------------------------
    // Methods
    // -------------------------------------------------------------------------

    public function isOwnedBy(User $user): bool
    {
        return $this->members()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }

    public static function forUser(User $user): ?self
    {
        return $user->businesses()
            ->wherePivot('is_active', true)
            ->first();
    }
}
