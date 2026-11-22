<x-filament-panels::page>
    @php($report = $this->getReport())
    @php($money = fn (float $value): string => ($value < 0 ? '-£' : '£').number_format(abs($value), 2))

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.balance_sheet.fields.period') }}</span>
                <select wire:model.live="periodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.balance_sheet.select_period') }}</option>
                    @foreach ($this->getPeriodOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.balance_sheet.fields.comparison_period') }}</span>
                <select wire:model.live="comparisonPeriodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.balance_sheet.no_comparison') }}</option>
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
                {{ __('finance.balance_sheet.no_periods') }}
            </div>
        @else
            <div class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $report['period']->name }}</h2>
                    <p class="text-sm text-gray-500">{{ __('finance.balance_sheet.as_at', ['date' => $report['period']->end_date]) }}</p>
                    @if ($report['comparison_period'])
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('finance.balance_sheet.compared_with', [
                                'period' => $report['comparison_period']->name,
                                'date' => $report['comparison_period']->end_date,
                            ]) }}
                        </p>
                    @endif
                </div>
                <div class="text-right text-sm text-gray-500">
                    <span class="inline-flex rounded-full px-3 py-1 font-semibold {{ $report['totals']->is_balanced ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300' }}">
                        {{ __('finance.balance_sheet.'.($report['totals']->is_balanced ? 'balanced' : 'unbalanced')) }}
                    </span>
                    <div class="mt-2">{{ __('finance.balance_sheet.summary.accounts') }}: {{ $report['totals']->account_count }}</div>
                    <div>{{ __('finance.balance_sheet.summary.journals') }}: {{ $report['totals']->journal_count }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['total_assets', 'total_liabilities', 'total_equity', 'difference'] as $metric)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-sm text-gray-500">{{ __('finance.balance_sheet.metrics.'.$metric) }}</div>
                        <div class="mt-1 text-2xl font-bold {{ $metric === 'difference' && $report['totals']->difference_gbp !== 0.0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $money($report['totals']->{$metric.'_gbp'}) }}
                        </div>
                        @if ($report['comparison_period'])
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('finance.balance_sheet.variance') }}: {{ $money($report['totals']->{'variance_'.$metric.'_gbp'}) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <div class="rounded-xl border border-primary-200 bg-primary-50 p-4 text-sm text-primary-800 dark:border-primary-900 dark:bg-primary-950/30 dark:text-primary-200">
                {{ __('finance.balance_sheet.earnings_notice') }}
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="w-28 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.balance_sheet.columns.code') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.balance_sheet.columns.account') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.balance_sheet.columns.current') }}</th>
                                @if ($report['comparison_period'])
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.balance_sheet.columns.comparison') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.balance_sheet.columns.variance') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($report['sections'] as $section)
                                <tr class="bg-gray-50/70 dark:bg-gray-800/70">
                                    <th colspan="{{ $report['comparison_period'] ? 5 : 3 }}" class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('finance.balance_sheet.sections.'.$section->key) }}
                                    </th>
                                </tr>
                                @forelse ($section->rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $row->code ?? '—' }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $row->is_synthetic ? __('finance.balance_sheet.synthetic.'.$row->synthetic_key) : $row->name }}
                                            @if (! $row->is_synthetic && ! $row->is_active)
                                                <span class="text-xs text-gray-500">· {{ __('finance.balance_sheet.inactive_account') }}</span>
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
                                            {{ __('finance.balance_sheet.empty_section') }}
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="font-semibold">
                                    <td colspan="2" class="px-4 py-3 text-right text-sm">
                                        {{ __('finance.balance_sheet.subtotal', ['section' => __('finance.balance_sheet.sections.'.$section->key)]) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">{{ $money($section->amount_gbp) }}</td>
                                    @if ($report['comparison_period'])
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->comparison_gbp) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->variance_gbp) }}</td>
                                    @endif
                                </tr>
                            @endforeach

                            @foreach (['total_assets', 'liabilities_and_equity', 'difference'] as $metric)
                                <tr class="bg-primary-50 font-bold dark:bg-primary-950/30">
                                    <td colspan="2" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                        {{ __('finance.balance_sheet.metrics.'.$metric) }}
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
