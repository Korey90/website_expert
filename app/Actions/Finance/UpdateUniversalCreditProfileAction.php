<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\UniversalCreditProfile;
use App\Models\User;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

class UpdateUniversalCreditProfileAction
{
    /**
     * @param array{
     *   is_enabled: bool,
     *   vat_reporting_method?: string|null,
     *   vat_method_effective_from?: string|null,
     *   default_assessment_period_start_day?: int|null
     * } $data
     */
    public function execute(Business $business, User $actor, array $data): UniversalCreditProfile
    {
        $this->authorize($business, $actor);

        return DB::transaction(function () use ($business, $actor, $data): UniversalCreditProfile {
            $accountingProfile = AccountingProfile::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($accountingProfile->entity_type !== 'sole_trader') {
                throw new DomainException(__('finance.universal_credit.errors.sole_trader_only'));
            }

            $profile = UniversalCreditProfile::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->lockForUpdate()
                ->first();
            $enabled = (bool) ($data['is_enabled'] ?? false);
            $startDay = $data['default_assessment_period_start_day'] ?? null;

            if ($startDay !== null && (! is_numeric($startDay) || (int) $startDay < 1 || (int) $startDay > 31)) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_start_day'));
            }

            [$vatMethod, $vatEffectiveFrom] = $this->vatSettings(
                $accountingProfile,
                $profile,
                $enabled,
                $data,
            );

            $attributes = [
                'is_enabled' => $enabled,
                'vat_reporting_method' => $vatMethod,
                'vat_method_effective_from' => $vatEffectiveFrom,
                'default_assessment_period_start_day' => $startDay === null ? null : (int) $startDay,
                'updated_by' => $actor->id,
            ];

            if ($profile) {
                $profile->update($attributes);

                return $profile->fresh();
            }

            return UniversalCreditProfile::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                ...$attributes,
                'created_by' => $actor->id,
            ]);
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{0: string|null, 1: string|null}
     */
    private function vatSettings(
        AccountingProfile $accountingProfile,
        ?UniversalCreditProfile $profile,
        bool $enabled,
        array $data,
    ): array {
        if (! $accountingProfile->vat_registered) {
            return [null, null];
        }

        $methodProvided = array_key_exists('vat_reporting_method', $data);
        $dateProvided = array_key_exists('vat_method_effective_from', $data);
        $method = $methodProvided ? $data['vat_reporting_method'] : $profile?->vat_reporting_method;
        $effectiveFrom = $dateProvided
            ? $data['vat_method_effective_from']
            : $profile?->vat_method_effective_from?->toDateString();

        if ($method !== null && ! in_array($method, UniversalCreditProfile::vatReportingMethods(), true)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_vat_method'));
        }

        if ($enabled && blank($method)) {
            throw new DomainException(__('finance.universal_credit.errors.vat_method_required'));
        }

        if ($enabled && blank($effectiveFrom)) {
            throw new DomainException(__('finance.universal_credit.errors.vat_effective_from_required'));
        }

        if (filled($effectiveFrom)) {
            try {
                $rawEffectiveFrom = (string) $effectiveFrom;
                $effectiveFrom = CarbonImmutable::createFromFormat('!Y-m-d', $rawEffectiveFrom);

                if ($effectiveFrom->format('Y-m-d') !== $rawEffectiveFrom) {
                    throw new \InvalidArgumentException('Non-canonical date.');
                }

                $effectiveFrom = $effectiveFrom->toDateString();
            } catch (\Throwable $exception) {
                throw new DomainException(__('finance.universal_credit.errors.invalid_vat_effective_from'), previous: $exception);
            }
        } else {
            $effectiveFrom = null;
        }

        return [$method, $effectiveFrom];
    }

    private function authorize(Business $business, User $actor): void
    {
        $isOwnerOrAdmin = $actor->businessMemberships()
            ->where('business_id', $business->id)
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $isOwnerOrAdmin || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.profile_manage_denied'));
        }
    }
}
