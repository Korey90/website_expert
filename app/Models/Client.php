<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Client extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_name', 'trading_name', 'companies_house_number', 'vat_number',
        'website', 'status', 'source', 'industry',
        'address_line1', 'address_line2', 'city', 'county', 'postcode', 'country',
        'primary_contact_name', 'primary_contact_email', 'primary_contact_phone',
        'assigned_to', 'lifetime_value', 'currency', 'notes', 'portal_user_id',
        'notify_email_transactional', 'notify_email_projects', 'notify_email_marketing',
        'notify_sms', 'communication_prefs_updated_at',
    ];

    protected $casts = [
        'lifetime_value'                => 'decimal:2',
        'notify_email_transactional'    => 'boolean',
        'notify_email_projects'         => 'boolean',
        'notify_email_marketing'        => 'boolean',
        'notify_sms'                    => 'boolean',
        'communication_prefs_updated_at'=> 'datetime',
    ];

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function portalUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'portal_user_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }

    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(Quote::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function getFullAddressAttribute(): string
    {
        return collect([$this->address_line1, $this->address_line2, $this->city, $this->county, $this->postcode])
            ->filter()
            ->implode(', ');
    }
}
