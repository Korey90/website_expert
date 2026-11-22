<?php

namespace App\Http\Requests\Business;

use App\Models\AccountingProfile;
use App\Support\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAccountingProfileRequest extends FormRequest
{
    private const VAT_SCHEMES = [
        'standard',
        'cash',
        'annual',
        'flat_rate',
    ];

    public function authorize(): bool
    {
        $business = currentBusiness();

        if (! $business || ! $this->user()) {
            return false;
        }

        return PermissionHelper::allows($this->user(), 'manage_finance')
            && $this->user()
                ->businessMemberships()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->whereIn('role', ['owner', 'admin'])
                ->exists();
    }

    protected function prepareForValidation(): void
    {
        $companyUtr = $this->input('company_utr');
        $vatNumber = $this->input('vat_number');

        $this->merge([
            'company_utr' => filled($companyUtr)
                ? preg_replace('/\s+/', '', (string) $companyUtr)
                : null,
            'vat_number' => filled($vatNumber)
                ? strtoupper(preg_replace('/\s+/', '', (string) $vatNumber))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'entity_type' => ['required', 'string', Rule::in(AccountingProfile::ENTITY_TYPES)],
            'legal_name' => ['required', 'string', 'min:2', 'max:255'],
            'company_number' => ['nullable', 'required_if:entity_type,limited_company', 'string', 'max:20'],
            'registered_address' => ['required', 'array'],
            'registered_address.line_1' => ['required', 'string', 'max:255'],
            'registered_address.line_2' => ['nullable', 'string', 'max:255'],
            'registered_address.city' => ['required', 'string', 'max:100'],
            'registered_address.county' => ['nullable', 'string', 'max:100'],
            'registered_address.postcode' => ['required', 'string', 'max:12'],
            'registered_address.country' => ['required', 'string', 'size:2'],
            'company_utr' => ['nullable', 'regex:/^\d{10}$/'],
            'functional_currency' => ['required', 'string', 'in:GBP'],
            'accounting_basis' => ['required', 'string', Rule::in(AccountingProfile::ACCOUNTING_BASES)],
            'financial_year_start' => ['required', 'date'],
            'financial_year_end' => ['required', 'date', 'after:financial_year_start'],
            'vat_registered' => ['required', 'boolean'],
            'vat_number' => ['nullable', 'required_if:vat_registered,true', 'regex:/^(?:GB)?(?:\d{9}(?:\d{3})?|GD\d{3}|HA\d{3})$/'],
            'vat_scheme' => ['nullable', 'required_if:vat_registered,true', Rule::in(self::VAT_SCHEMES)],
            'vat_effective_from' => ['nullable', 'required_if:vat_registered,true', 'date'],
            'vat_effective_to' => ['nullable', 'date', 'after_or_equal:vat_effective_from'],
            'paye_reference' => ['nullable', 'string', 'max:32'],
            'accounts_office_reference' => ['nullable', 'string', 'max:32'],
        ];
    }
}
