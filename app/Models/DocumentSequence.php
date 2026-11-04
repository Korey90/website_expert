<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentSequence extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'business_id',
        'document_type',
        'year',
        'next_number',
        'prefix',
        'padding',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'next_number' => 'integer',
            'padding' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}
