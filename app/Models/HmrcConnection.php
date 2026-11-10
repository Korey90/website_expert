<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HmrcConnection extends Model
{
    use BelongsToTenant;

    public const STATUS_CONNECTED = 'connected';

    public const STATUS_RECONNECT_REQUIRED = 'reconnect_required';

    public const STATUS_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'business_id',
        'environment',
        'scopes',
        'access_token',
        'refresh_token',
        'vrn',
        'expires_at',
        'status',
        'connected_at',
        'connected_by',
        'refreshed_at',
        'refreshed_by',
        'disconnected_at',
        'disconnected_by',
        'last_successful_call_at',
        'reconnect_reason',
    ];

    protected $hidden = [
        'access_token',
        'refresh_token',
        'vrn',
    ];

    protected function casts(): array
    {
        return [
            'scopes' => 'array',
            'access_token' => 'encrypted',
            'refresh_token' => 'encrypted',
            'vrn' => 'encrypted',
            'expires_at' => 'datetime',
            'connected_at' => 'datetime',
            'refreshed_at' => 'datetime',
            'disconnected_at' => 'datetime',
            'last_successful_call_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function connectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'connected_by');
    }

    public function refreshedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refreshed_by');
    }

    public function disconnectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disconnected_by');
    }

    public function isConnected(): bool
    {
        return $this->status === self::STATUS_CONNECTED
            && filled($this->access_token)
            && filled($this->refresh_token)
            && $this->expires_at !== null;
    }
}
