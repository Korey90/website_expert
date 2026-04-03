<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ApiToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'business_id', 'name', 'token_hash',
        'last_used_at', 'expires_at', 'is_active', 'created_by',
    ];

    protected $casts = [
        'is_active'    => 'boolean',
        'last_used_at' => 'datetime',
        'expires_at'   => 'datetime',
    ];

    // -----------------------------------------------------------------
    // Relations
    // -----------------------------------------------------------------

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // -----------------------------------------------------------------
    // Scopes
    // -----------------------------------------------------------------

    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('expires_at')
                ->orWhere('expires_at', '>', now())
            );
    }

    // -----------------------------------------------------------------
    // Static helpers
    // -----------------------------------------------------------------

    /**
     * Find a token by its plain-text value.
     * Updates last_used_at on successful lookup.
     */
    public static function findByToken(string $plainToken): ?self
    {
        $hash = hash('sha256', $plainToken);

        return static::active()
            ->where('token_hash', $hash)
            ->first();
    }

    /**
     * Generate a new plain token + its hash.
     * Plain token is returned ONCE — never stored.
     *
     * @return array{plain: string, hash: string}
     */
    public static function generateToken(): array
    {
        $plain = bin2hex(random_bytes(32)); // 256-bit entropy

        return [
            'plain' => $plain,
            'hash'  => hash('sha256', $plain),
        ];
    }
}
