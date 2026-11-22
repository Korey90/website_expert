<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Translatable\HasTranslations;

class FixedAssetCategory extends Model
{
    use BelongsToTenant, HasFactory, HasTranslations;

    public const METHOD_STRAIGHT_LINE = 'straight_line';

    public const METHOD_REDUCING_BALANCE = 'reducing_balance';

    public const METHOD_NONE = 'none';

    public const DEPRECIATION_METHODS = [
        self::METHOD_STRAIGHT_LINE,
        self::METHOD_REDUCING_BALANCE,
        self::METHOD_NONE,
    ];

    public array $translatable = ['name'];

    protected $fillable = [
        'business_id', 'code', 'name', 'asset_account_id', 'accumulated_depreciation_account_id',
        'depreciation_expense_account_id', 'default_depreciation_method',
        'default_useful_life_months', 'default_residual_value_percent', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'default_useful_life_months' => 'integer',
            'default_residual_value_percent' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::deleting(function (FixedAssetCategory $category): void {
            if (FixedAsset::withoutGlobalScopes()
                ->withTrashed()
                ->where('fixed_asset_category_id', $category->id)
                ->exists()) {
                throw new DomainException('A fixed asset category in use cannot be deleted. Deactivate it instead.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assetAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'asset_account_id');
    }

    public function accumulatedDepreciationAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'accumulated_depreciation_account_id');
    }

    public function depreciationExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'depreciation_expense_account_id');
    }

    public function fixedAssets(): HasMany
    {
        return $this->hasMany(FixedAsset::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
