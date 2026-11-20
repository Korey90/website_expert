<?php

namespace App\Http\Requests\Finance;

use App\Models\Expense;
use App\Models\ExpenseItem;
use App\Support\PermissionHelper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\Validator;

abstract class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $business = currentBusiness();

        return $business !== null
            && PermissionHelper::allows($this->user(), 'manage_finance')
            && $this->user()?->businessMemberships()
                ->where('business_id', $business->id)
                ->where('is_active', true)
                ->exists();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'currency' => strtoupper((string) $this->input('currency', 'GBP')),
            'supplier_invoice_number' => filled($this->input('supplier_invoice_number'))
                ? trim((string) $this->input('supplier_invoice_number'))
                : null,
        ]);
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        $businessId = currentBusiness()?->id;
        $tenantExists = fn (string $table) => Rule::exists($table, 'id')
            ->where(fn ($query) => $query->where('business_id', $businessId));
        $taxCodeExists = Rule::exists('tax_codes', 'id')
            ->where(fn ($query) => $query
                ->where('business_id', $businessId)
                ->where('is_active', true)
                ->whereDate('valid_from', '<=', $this->input('tax_point'))
                ->where(fn ($dates) => $dates
                    ->whereNull('valid_to')
                    ->orWhereDate('valid_to', '>=', $this->input('tax_point'))));

        return [
            'supplier_id' => ['nullable', 'integer', $tenantExists('suppliers')],
            'supplier_invoice_number' => ['nullable', 'string', 'max:255', $this->supplierInvoiceUniqueRule($businessId)],
            'client_id' => ['nullable', 'integer', $tenantExists('clients')],
            'project_id' => ['nullable', 'integer', $tenantExists('projects')],
            'currency' => ['required', 'string', Rule::in(array_keys(config('currencies.supported', [])))],
            'gbp_exchange_rate' => ['nullable', 'required_unless:currency,GBP', 'numeric', 'gt:0'],
            'exchange_rate_source' => ['nullable', 'required_unless:currency,GBP', 'string', 'max:100'],
            'exchange_rate_date' => ['nullable', 'required_unless:currency,GBP', 'date'],
            'issue_date' => ['required', 'date'],
            'tax_point' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:issue_date'],
            'received_date' => ['nullable', 'date'],
            'payment_method' => ['nullable', 'string', Rule::in(['bank_transfer', 'card', 'cash', 'direct_debit', 'cheque', 'other'])],
            'business_purpose' => ['required', 'string', 'max:2000'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.description' => ['required', 'string', 'max:255'],
            'items.*.quantity' => ['required', 'numeric', 'gt:0'],
            'items.*.unit_price' => ['required', 'numeric', 'decimal:0,'.ExpenseItem::UNIT_PRICE_SCALE, 'min:0'],
            'items.*.supply_date' => ['nullable', 'date'],
            'items.*.tax_code_id' => ['required', 'integer', $taxCodeExists],
            'items.*.ledger_account_id' => [
                'nullable',
                'integer',
                Rule::exists('ledger_accounts', 'id')->where(fn ($query) => $query
                    ->where('business_id', $businessId)
                    ->where('is_active', true)
                    ->where('is_postable', true)),
            ],
            'items.*.category' => ['sometimes', 'string', Rule::in(config('finance.expense_categories', []))],
            'items.*.client_id' => ['nullable', 'integer', $tenantExists('clients')],
            'items.*.project_id' => ['nullable', 'integer', $tenantExists('projects')],
            'items.*.billable' => ['sometimes', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($this->input('items', []) as $index => $item) {
                if (($item['billable'] ?? false) && blank($item['client_id'] ?? null)) {
                    $validator->errors()->add(
                        "items.{$index}.client_id",
                        'A client is required for a billable expense item.',
                    );
                }
            }
        });
    }

    private function supplierInvoiceUniqueRule(?string $businessId): Unique
    {
        $rule = Rule::unique('expenses', 'supplier_invoice_number')
            ->where(fn ($query) => $query
                ->where('business_id', $businessId)
                ->where('supplier_id', $this->input('supplier_id')));
        $expense = $this->route('expense');

        if ($expense instanceof Expense) {
            $rule->ignore($expense->id);
        } elseif (is_numeric($expense)) {
            $rule->ignore((int) $expense);
        }

        return $rule;
    }
}
