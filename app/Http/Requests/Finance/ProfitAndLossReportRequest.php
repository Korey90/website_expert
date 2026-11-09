<?php

namespace App\Http\Requests\Finance;

use App\Support\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfitAndLossReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PermissionHelper::allows($this->user(), 'view_finance') && currentBusiness() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $tenantPeriod = fn () => Rule::exists('accounting_periods', 'id')
            ->where('business_id', currentBusiness()?->id);

        return [
            'period_id' => ['required', 'integer', $tenantPeriod()],
            'comparison_period_id' => ['nullable', 'integer', 'different:period_id', $tenantPeriod()],
        ];
    }
}
