<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CalculatorPricing extends Model
{
    use HasFactory;

    protected $table = 'calculator_pricing';

    protected $fillable = [
        'category', 'key', 'label', 'description',
        'base_cost', 'monthly_cost', 'cost_formula',
        'currency', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'base_cost'    => 'decimal:2',
        'monthly_cost' => 'decimal:2',
        'is_active'    => 'boolean',
    ];
}
