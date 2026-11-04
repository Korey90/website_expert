<x-filament-panels::page>
    @php($report = $this->getReport())

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-4">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.expense_report.fields.date_from') }}</span>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.expense_report.fields.date_to') }}</span>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.expense_report.fields.group_by') }}</span>
                <select wire:model.live="groupBy" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    @foreach (\App\Services\Finance\ExpenseReportService::GROUPS as $group)
                        <option value="{{ $group }}">{{ __('finance.expense_report.groups.'.$group) }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                <a href="{{ $this->getExportUrl() }}" class="inline-flex w-full items-center justify-center rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white hover:bg-primary-500">
                    {{ __('finance.expense_report.export_csv') }}
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach (['net_gbp', 'vat_gbp', 'gross_gbp'] as $field)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-sm text-gray-500">{{ __('finance.expense_report.columns.'.$field) }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">£{{ number_format($report['totals']->{$field}, 2) }}</div>
                </div>
            @endforeach
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500">{{ __('finance.expense_report.columns.documents') }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $report['totals']->document_count }}</div>
            </div>
        </div>

        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach (['group', 'documents', 'net_gbp', 'vat_gbp', 'gross_gbp'] as $column)
                                <th class="px-6 py-3 {{ $column === 'group' ? 'text-left' : 'text-right' }} text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.expense_report.columns.'.$column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['rows'] as $row)
                            <tr>
                                <td class="px-6 py-3 text-sm font-medium text-gray-900 dark:text-white">{{ $row->label }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $row->document_count }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-300">£{{ number_format($row->net_gbp, 2) }}</td>
                                <td class="px-6 py-3 text-right text-sm text-gray-600 dark:text-gray-300">£{{ number_format($row->vat_gbp, 2) }}</td>
                                <td class="px-6 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">£{{ number_format($row->gross_gbp, 2) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('finance.expense_report.empty') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-filament-panels::page>
