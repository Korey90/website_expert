<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;

class TaxCode extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations;

    public const TYPES = [
        'standard',
        'reduced',
        'zero',
        'exempt',
        'outside_scope',
        'reverse_charge',
    ];

    public array $translatable = ['label'];

    protected $fillable = [
        'business_id',
        'code',
        'label',
        'type',
        'rate',
        'input_eligible',
        'output_eligible',
        'vat_return_mapping',
        'valid_from',
        'valid_to',
        'is_active',
        'is_system',
    ];

    protected function casts(): array
    {
        return [
            'rate' => 'decimal:2',
            'input_eligible' => 'boolean',
            'output_eligible' => 'boolean',
            'vat_return_mapping' => 'array',
            'valid_from' => 'date',
            'valid_to' => 'date',
            'is_active' => 'boolean',
            'is_system' => 'boolean',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function scopeEffectiveOn(Builder $query, CarbonInterface|string $date): Builder
    {
        return $query
            ->where('is_active', true)
            ->whereDate('valid_from', '<=', $date)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $date);
            });
    }
}
