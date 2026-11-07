<x-filament-panels::page>
    @php($report = $this->getReport())

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.trial_balance.fields.period') }}</span>
                <select wire:model.live="periodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.trial_balance.select_period') }}</option>
                    @foreach ($this->getPeriodOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.trial_balance.fields.comparison_period') }}</span>
                <select wire:model.live="comparisonPeriodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.trial_balance.no_comparison') }}</option>
                    @foreach ($this->getComparisonOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($this->getExportUrl())
                <a href="{{ $this->getExportUrl() }}" class="fi-btn fi-btn-color-primary fi-color-primary rounded-lg px-4 py-2 text-center text-sm font-semibold text-white shadow-sm">
                    {{ __('finance.trial_balance.export_csv') }}
                </a>
            @endif
        </div>

        @if (! $report)
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                {{ __('finance.trial_balance.no_periods') }}
            </div>
        @else
            <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ $report['period']->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $report['period']->start_date }} – {{ $report['period']->end_date }} · {{ __('finance.accounting_periods.statuses.'.$report['period']->status) }}</p>
                    @if ($report['comparison_period'])
                        <p class="mt-1 text-xs text-gray-500">{{ __('finance.trial_balance.compared_with', ['period' => $report['comparison_period']->name, 'date' => $report['comparison_period']->end_date]) }}</p>
                    @endif
                </div>
                <span class="rounded-full px-3 py-1 text-sm font-semibold {{ $report['totals']->is_balanced ? 'bg-success-50 text-success-700 dark:bg-success-950 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950 dark:text-danger-300' }}">
                    {{ $report['totals']->is_balanced ? __('finance.trial_balance.balanced') : __('finance.trial_balance.unbalanced') }}
                </span>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach ([
                    'closing_debit_gbp' => $report['totals']->closing_debit_gbp,
                    'closing_credit_gbp' => $report['totals']->closing_credit_gbp,
                    'closing_difference_gbp' => $report['totals']->closing_difference_gbp,
                ] as $field => $value)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-sm text-gray-500">{{ __('finance.trial_balance.summary.'.$field) }}</div>
                        <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">£{{ number_format($value, 2) }}</div>
                    </div>
                @endforeach
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-sm text-gray-500">{{ __('finance.trial_balance.summary.accounts_journals') }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $report['totals']->account_count }} / {{ $report['totals']->journal_count }}</div>
                </div>
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="min-w-max divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th rowspan="2" class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.trial_balance.columns.code') }}</th>
                                <th rowspan="2" class="min-w-64 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.trial_balance.columns.account') }}</th>
                                @foreach (['opening', 'period', 'closing'] as $group)
                                    <th colspan="2" class="border-l border-gray-200 px-4 py-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">{{ __('finance.trial_balance.groups.'.$group) }}</th>
                                @endforeach
                                @if ($report['comparison_period'])
                                    <th colspan="2" class="border-l border-gray-200 px-4 py-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">{{ __('finance.trial_balance.groups.comparison') }}</th>
                                    <th colspan="2" class="border-l border-gray-200 px-4 py-2 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:border-gray-700">{{ __('finance.trial_balance.groups.variance') }}</th>
                                @endif
                            </tr>
                            <tr>
                                @foreach (range(1, $report['comparison_period'] ? 5 : 3) as $group)
                                    <th class="border-l border-gray-200 px-4 py-2 text-right text-xs font-medium text-gray-500 dark:border-gray-700">{{ __('finance.trial_balance.debit_short') }}</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500">{{ __('finance.trial_balance.credit_short') }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['rows'] as $row)
                                <tr>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $row->code }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                        <div>{{ $row->name }}</div>
                                        <div class="text-xs text-gray-500">{{ __('finance.ledger_accounts.types.'.$row->type) }}@if (! $row->is_active) · {{ __('finance.trial_balance.inactive_account') }}@endif</div>
                                    </td>
                                    @foreach (['opening', 'period', 'closing'] as $group)
                                        <td class="border-l border-gray-100 px-4 py-3 text-right text-sm dark:border-gray-800">{{ $row->{$group.'_debit_gbp'} > 0 ? '£'.number_format($row->{$group.'_debit_gbp'}, 2) : '—' }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $row->{$group.'_credit_gbp'} > 0 ? '£'.number_format($row->{$group.'_credit_gbp'}, 2) : '—' }}</td>
                                    @endforeach
                                    @if ($report['comparison_period'])
                                        @foreach (['comparison', 'variance'] as $group)
                                            <td class="border-l border-gray-100 px-4 py-3 text-right text-sm dark:border-gray-800">{{ $row->{$group.'_debit_gbp'} > 0 ? '£'.number_format($row->{$group.'_debit_gbp'}, 2) : '—' }}</td>
                                            <td class="px-4 py-3 text-right text-sm">{{ $row->{$group.'_credit_gbp'} > 0 ? '£'.number_format($row->{$group.'_credit_gbp'}, 2) : '—' }}</td>
                                        @endforeach
                                    @endif
                                </tr>
                            @empty
                                <tr><td colspan="{{ $report['comparison_period'] ? 12 : 8 }}" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('finance.trial_balance.empty') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold dark:bg-gray-800">
                            <tr>
                                <td colspan="2" class="px-4 py-3 text-right text-sm">{{ __('finance.trial_balance.total') }}</td>
                                @foreach (['opening', 'period', 'closing'] as $group)
                                    <td class="border-l border-gray-200 px-4 py-3 text-right text-sm dark:border-gray-700">£{{ number_format($report['totals']->{$group.'_debit_gbp'}, 2) }}</td>
                                    <td class="px-4 py-3 text-right text-sm">£{{ number_format($report['totals']->{$group.'_credit_gbp'}, 2) }}</td>
                                @endforeach
                                @if ($report['comparison_period'])
                                    @foreach (['comparison', 'variance'] as $group)
                                        <td class="border-l border-gray-200 px-4 py-3 text-right text-sm dark:border-gray-700">£{{ number_format($report['totals']->{$group.'_debit_gbp'}, 2) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">£{{ number_format($report['totals']->{$group.'_credit_gbp'}, 2) }}</td>
                                    @endforeach
                                @endif
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
