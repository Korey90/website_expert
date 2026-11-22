<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FixedAsset extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_DRAFT = 'draft';

    protected $fillable = [
        'business_id', 'fixed_asset_category_id', 'number', 'name', 'description',
        'serial_number', 'location', 'status', 'depreciation_method', 'useful_life_months',
        'residual_value_percent', 'notes', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'useful_life_months' => 'integer',
            'residual_value_percent' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::updating(function (FixedAsset $asset): void {
            if ($asset->getOriginal('status') !== self::STATUS_DRAFT) {
                throw new DomainException('A non-draft fixed asset is immutable and requires a controlled asset workflow.');
            }
        });

        static::deleting(function (FixedAsset $asset): void {
            if ($asset->status !== self::STATUS_DRAFT) {
                throw new DomainException('Only a draft fixed asset can be deleted.');
            }
        });
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(FixedAssetCategory::class, 'fixed_asset_category_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(FinancialAttachment::class, 'attachable');
    }
}
