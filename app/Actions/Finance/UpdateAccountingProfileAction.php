<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Arr;

class UpdateAccountingProfileAction
{
    private const SENSITIVE_FIELDS = [
        'company_utr',
        'paye_reference',
        'accounts_office_reference',
    ];

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(AccountingProfile $profile, User $actor, array $data): AccountingProfile
    {
        $this->authorize($profile, $actor);

        $attributes = Arr::except($data, self::SENSITIVE_FIELDS);

        if ($profile->entity_type === 'sole_trader'
            && ($attributes['entity_type'] ?? null) !== 'sole_trader'
            && ExpensePayment::withoutGlobalScopes()
                ->where('business_id', $profile->business_id)
                ->where('settlement_source', ExpensePayment::SOURCE_OWNER_FUNDS)
                ->whereHas('journalEntries')
                ->exists()) {
            throw new DomainException('The entity type cannot be changed while posted owner-funded payments exist.');
        }

        if (($attributes['entity_type'] ?? null) !== 'limited_company') {
            $attributes['company_number'] = null;
        }

        if (! ($attributes['vat_registered'] ?? false)) {
            $attributes = [
                ...$attributes,
                'vat_number' => null,
                'vat_scheme' => null,
                'vat_effective_from' => null,
                'vat_effective_to' => null,
            ];
        }

        foreach (self::SENSITIVE_FIELDS as $field) {
            if (filled($data[$field] ?? null)) {
                $attributes[$field] = $data[$field];
            }
        }

        $attributes['updated_by'] = $actor->id;

        $profile->update($attributes);

        return $profile->fresh();
    }

    private function authorize(AccountingProfile $profile, User $actor): void
    {
        $membership = $actor->businessMemberships()
            ->where('business_id', $profile->business_id)
            ->where('is_active', true)
            ->whereIn('role', ['owner', 'admin'])
            ->exists();

        if (! $membership) {
            throw new DomainException('Only an active business owner or administrator can update the accounting profile.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot manage the accounting profile.');
        }
    }
}
