<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\User;
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
        $attributes = Arr::except($data, self::SENSITIVE_FIELDS);

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
}
