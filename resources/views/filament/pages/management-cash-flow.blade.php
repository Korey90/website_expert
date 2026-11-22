<x-filament-panels::page>
    @php($report = $this->getReport())
    @php($money = fn (float $value): string => ($value < 0 ? '-£' : '£').number_format(abs($value), 2))

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.management_cash_flow.fields.period') }}</span>
                <select wire:model.live="periodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.management_cash_flow.select_period') }}</option>
                    @foreach ($this->getPeriodOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.management_cash_flow.fields.comparison_period') }}</span>
                <select wire:model.live="comparisonPeriodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.management_cash_flow.no_comparison') }}</option>
                    @foreach ($this->getComparisonOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($this->getExportUrl())
                @include('filament.pages.partials.report-export-buttons')
            @endif
        </div>

        @if (! $report)
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">
                {{ __('finance.management_cash_flow.no_periods') }}
            </div>
        @else
            <div class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $report['period']->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $report['period']->start_date }} – {{ $report['period']->end_date }}</p>
                    @if ($report['comparison_period'])
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('finance.management_cash_flow.compared_with', [
                                'period' => $report['comparison_period']->name,
                                'from' => $report['comparison_period']->start_date,
                                'to' => $report['comparison_period']->end_date,
                            ]) }}
                        </p>
                    @endif
                </div>
                <div class="text-right text-sm text-gray-500">
                    <span class="inline-flex rounded-full px-3 py-1 font-semibold {{ $report['totals']->is_reconciled ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300' }}">
                        {{ __('finance.management_cash_flow.'.($report['totals']->is_reconciled ? 'reconciled' : 'unreconciled')) }}
                    </span>
                    <div class="mt-2">{{ __('finance.management_cash_flow.summary.cash_accounts') }}: {{ $report['totals']->cash_account_count }}</div>
                    <div>{{ __('finance.management_cash_flow.summary.counterpart_accounts') }}: {{ $report['totals']->account_count }}</div>
                    <div>{{ __('finance.management_cash_flow.summary.journals') }}: {{ $report['totals']->journal_count }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['operating_cash_flow', 'investing_cash_flow', 'financing_cash_flow', 'net_cash_flow'] as $metric)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-sm text-gray-500">{{ __('finance.management_cash_flow.metrics.'.$metric) }}</div>
                        <div class="mt-1 text-2xl font-bold {{ $report['totals']->{$metric.'_gbp'} < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $money($report['totals']->{$metric.'_gbp'}) }}
                        </div>
                        @if ($report['comparison_period'])
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('finance.management_cash_flow.variance') }}: {{ $money($report['totals']->{'variance_'.$metric.'_gbp'}) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800 dark:border-primary-900 dark:bg-primary-950/30 dark:text-primary-200">
                {{ __('finance.management_cash_flow.management_notice') }}
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="w-28 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.management_cash_flow.columns.code') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.management_cash_flow.columns.account') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.management_cash_flow.columns.current') }}</th>
                                @if ($report['comparison_period'])
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.management_cash_flow.columns.comparison') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.management_cash_flow.columns.variance') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($report['sections'] as $section)
                                <tr class="bg-gray-50/70 dark:bg-gray-800/70">
                                    <th colspan="{{ $report['comparison_period'] ? 5 : 3 }}" class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('finance.management_cash_flow.sections.'.$section->key) }}
                                    </th>
                                </tr>
                                @forelse ($section->rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $row->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $row->name }}
                                            @if (! $row->is_active)
                                                <span class="text-xs text-gray-500">· {{ __('finance.management_cash_flow.inactive_account') }}</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($row->amount_gbp) }}</td>
                                        @if ($report['comparison_period'])
                                            <td class="px-4 py-3 text-right text-sm">{{ $money($row->comparison_gbp) }}</td>
                                            <td class="px-4 py-3 text-right text-sm">{{ $money($row->variance_gbp) }}</td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $report['comparison_period'] ? 5 : 3 }}" class="px-4 py-3 text-sm text-gray-500">
                                            {{ __('finance.management_cash_flow.empty_section') }}
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="font-semibold">
                                    <td colspan="2" class="px-4 py-3 text-right text-sm">
                                        {{ __('finance.management_cash_flow.subtotal', ['section' => __('finance.management_cash_flow.sections.'.$section->key)]) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">{{ $money($section->amount_gbp) }}</td>
                                    @if ($report['comparison_period'])
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->comparison_gbp) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->variance_gbp) }}</td>
                                    @endif
                                </tr>
                            @endforeach

                            @foreach (['opening_cash', 'cash_inflows', 'cash_outflows', 'net_cash_flow', 'closing_cash', 'difference'] as $metric)
                                <tr class="bg-primary-50 font-bold dark:bg-primary-950/30">
                                    <td colspan="2" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                        {{ __('finance.management_cash_flow.metrics.'.$metric) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{$metric.'_gbp'}) }}</td>
                                    @if ($report['comparison_period'])
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{'comparison_'.$metric.'_gbp'}) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{'variance_'.$metric.'_gbp'}) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
