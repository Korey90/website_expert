<?php

namespace App\Http\Requests\Finance;

use App\Models\Expense;

class UpdateExpenseRequest extends ExpenseRequest
{
    public function authorize(): bool
    {
        if (! parent::authorize()) {
            return false;
        }

        $expense = $this->route('expense');

        return ! ($expense instanceof Expense)
            || ($expense->business_id === currentBusiness()?->id && $expense->status === Expense::STATUS_DRAFT);
    }
}
