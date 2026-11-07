<?php

namespace App\Http\Requests\Finance;

use App\Support\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BankReconciliationReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PermissionHelper::allows($this->user(), 'view_finance') && currentBusiness() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d', 'after_or_equal:date_from'],
            'account_id' => [
                'nullable',
                'integer',
                Rule::exists('bank_accounts', 'id')
                    ->where('business_id', currentBusiness()?->id),
            ],
        ];
    }
}
