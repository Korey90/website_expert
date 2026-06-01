<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DomainPriceList extends Model
{
    use HasFactory;

    protected $table = 'domain_price_list';

    protected $fillable = [
        'tld',
        'register_price',
        'renew_price',
        'transfer_price',
        'wholesale_register',
        'wholesale_renew',
        'wholesale_transfer',
        'margin_percent',
        'currency',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'register_price'      => 'decimal:2',
        'renew_price'         => 'decimal:2',
        'transfer_price'      => 'decimal:2',
        'wholesale_register'  => 'decimal:2',
        'wholesale_renew'     => 'decimal:2',
        'wholesale_transfer'  => 'decimal:2',
        'margin_percent'      => 'decimal:2',
        'is_active'           => 'boolean',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function forTld(string $tld): ?self
    {
        return static::where('tld', $tld)->where('is_active', true)->first();
    }
}
