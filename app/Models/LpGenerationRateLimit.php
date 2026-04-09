<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LpGenerationRateLimit extends Model
{
    protected $fillable = [
        'business_id',
        'year',
        'month',
        'count',
    ];

    protected function casts(): array
    {
        return [
            'year'  => 'integer',
            'month' => 'integer',
            'count' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
