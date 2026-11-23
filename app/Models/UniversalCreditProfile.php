<?php

namespace App\Models;

use App\Traits\BelongsToTenant;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversalCreditProfile extends Model
{
    use BelongsToTenant, HasFactory;

    public const VAT_INCLUSIVE = 'inclusive';

    public const VAT_EXCLUSIVE = 'exclusive';

    protected $fillable = [
        'business_id', 'is_enabled', 'vat_reporting_method', 'vat_method_effective_from',
        'default_assessment_period_start_day', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'is_enabled' => 'boolean',
            'vat_method_effective_from' => 'date',
            'default_assessment_period_start_day' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        $validate = function (self $profile): void {
            if ($profile->vat_reporting_method !== null
                && ! in_array($profile->vat_reporting_method, self::vatReportingMethods(), true)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_vat_method'));
            }

            if (($profile->vat_reporting_method === null) !== ($profile->vat_method_effective_from === null)) {
                throw new DomainException(__('finance.universal_credit.errors.vat_method_incomplete'));
            }

            $day = $profile->default_assessment_period_start_day;

            if ($day !== null && ($day < 1 || $day > 31)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_start_day'));
            }
        };

        static::creating($validate);
        static::updating(function (self $profile) use ($validate): void {
            if ($profile->isDirty('business_id')) {
                throw new DomainException(__('finance.universal_credit.errors.profile_tenant_immutable'));
            }

            $validate($profile);
        });
    }

    /** @return list<string> */
    public static function vatReportingMethods(): array
    {
        return [self::VAT_INCLUSIVE, self::VAT_EXCLUSIVE];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function assessmentPeriods(): HasMany
    {
        return $this->hasMany(UniversalCreditAssessmentPeriod::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
