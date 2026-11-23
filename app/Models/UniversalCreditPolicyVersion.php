<?php

namespace App\Models;

use App\Services\Finance\UniversalCreditPolicyHashService;
use DomainException;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UniversalCreditPolicyVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'version', 'effective_from', 'effective_to', 'verified_at',
        'source_urls', 'rules', 'rules_hash', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'version' => 'integer',
            'effective_from' => 'date',
            'effective_to' => 'date',
            'verified_at' => 'datetime',
            'source_urls' => 'array',
            'rules' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $policy): void {
            if (! is_array($policy->rules)
                || ! hash_equals(
                    (string) $policy->rules_hash,
                    app(UniversalCreditPolicyHashService::class)->hash($policy->rules),
                )) {
                throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
            }
        });

        static::updating(function (self $policy): void {
            if ($policy->isDirty([
                'code', 'version', 'effective_from', 'verified_at',
                'source_urls', 'rules', 'rules_hash',
            ])) {
                throw new DomainException(__('finance.universal_credit.errors.policy_immutable'));
            }
        });

        static::deleting(function (): void {
            throw new DomainException(__('finance.universal_credit.errors.policy_cannot_delete'));
        });
    }

    public function assessmentPeriods(): HasMany
    {
        return $this->hasMany(UniversalCreditAssessmentPeriod::class, 'policy_version_id');
    }
}
