<?php

namespace App\Http\Requests\Finance;

use App\Support\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;

class AgedReceivablesPayablesReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return PermissionHelper::allows($this->user(), 'view_finance') && currentBusiness() !== null;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'as_of' => ['required', 'date_format:Y-m-d'],
        ];
    }
}
