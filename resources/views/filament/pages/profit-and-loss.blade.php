<x-filament-panels::page>
    @php($report = $this->getReport())
    @php($money = fn (float $value): string => ($value < 0 ? '-£' : '£').number_format(abs($value), 2))

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.profit_and_loss.fields.period') }}</span>
                <select wire:model.live="periodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.profit_and_loss.select_period') }}</option>
                    @foreach ($this->getPeriodOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.profit_and_loss.fields.comparison_period') }}</span>
                <select wire:model.live="comparisonPeriodId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.profit_and_loss.no_comparison') }}</option>
                    @foreach ($this->getComparisonOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            @if ($this->getExportUrl())
                <a href="{{ $this->getExportUrl() }}" class="fi-btn fi-btn-color-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white">
                    {{ __('finance.profit_and_loss.export_csv') }}
                </a>
            @endif
        </div>

        @if (! $report)
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">
                {{ __('finance.profit_and_loss.no_periods') }}
            </div>
        @else
            <div class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ $report['period']->name }}</h2>
                    <p class="text-sm text-gray-500">{{ $report['period']->start_date }} – {{ $report['period']->end_date }}</p>
                    @if ($report['comparison_period'])
                        <p class="mt-1 text-sm text-gray-500">
                            {{ __('finance.profit_and_loss.compared_with', [
                                'period' => $report['comparison_period']->name,
                                'from' => $report['comparison_period']->start_date,
                                'to' => $report['comparison_period']->end_date,
                            ]) }}
                        </p>
                    @endif
                </div>
                <div class="text-right text-sm text-gray-500">
                    <div>{{ __('finance.profit_and_loss.summary.accounts') }}: {{ $report['totals']->account_count }}</div>
                    <div>{{ __('finance.profit_and_loss.summary.journals') }}: {{ $report['totals']->journal_count }}</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
                @foreach (['sales', 'gross_profit', 'operating_profit', 'net_profit'] as $metric)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-sm text-gray-500">{{ __('finance.profit_and_loss.metrics.'.$metric) }}</div>
                        <div class="mt-1 text-2xl font-bold {{ $report['totals']->{$metric.'_gbp'} < 0 ? 'text-danger-600 dark:text-danger-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $money($report['totals']->{$metric.'_gbp'}) }}
                        </div>
                        @if ($report['comparison_period'])
                            <div class="mt-1 text-xs text-gray-500">
                                {{ __('finance.profit_and_loss.variance') }}: {{ $money($report['totals']->{'variance_'.$metric.'_gbp'}) }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[720px] divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                <th class="w-28 px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.profit_and_loss.columns.code') }}</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.profit_and_loss.columns.account') }}</th>
                                <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.profit_and_loss.columns.current') }}</th>
                                @if ($report['comparison_period'])
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.profit_and_loss.columns.comparison') }}</th>
                                    <th class="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.profit_and_loss.columns.variance') }}</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach ($report['sections'] as $section)
                                <tr class="bg-gray-50/70 dark:bg-gray-800/70">
                                    <th colspan="{{ $report['comparison_period'] ? 5 : 3 }}" class="px-4 py-3 text-left text-sm font-semibold text-gray-900 dark:text-white">
                                        {{ __('finance.profit_and_loss.sections.'.$section->key) }}
                                    </th>
                                </tr>
                                @forelse ($section->rows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $row->code }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-700 dark:text-gray-300">
                                            {{ $row->name }}
                                            @if (! $row->is_active)
                                                <span class="text-xs text-gray-500">· {{ __('finance.profit_and_loss.inactive_account') }}</span>
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
                                            {{ __('finance.profit_and_loss.empty_section') }}
                                        </td>
                                    </tr>
                                @endforelse
                                <tr class="font-semibold">
                                    <td colspan="2" class="px-4 py-3 text-right text-sm">
                                        {{ __('finance.profit_and_loss.subtotal', ['section' => __('finance.profit_and_loss.sections.'.$section->key)]) }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">{{ $money($section->amount_gbp) }}</td>
                                    @if ($report['comparison_period'])
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->comparison_gbp) }}</td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($section->variance_gbp) }}</td>
                                    @endif
                                </tr>

                                @php($calculatedMetric = match ($section->key) {
                                    'cost_of_sales' => 'gross_profit',
                                    'operating_expenses' => 'operating_profit',
                                    'other_expenses' => 'profit_before_tax',
                                    'corporation_tax' => 'net_profit',
                                    default => null,
                                })
                                @if ($calculatedMetric)
                                    <tr class="bg-primary-50 font-bold dark:bg-primary-950/30">
                                        <td colspan="2" class="px-4 py-3 text-right text-sm text-gray-900 dark:text-white">
                                            {{ __('finance.profit_and_loss.metrics.'.$calculatedMetric) }}
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{$calculatedMetric.'_gbp'}) }}</td>
                                        @if ($report['comparison_period'])
                                            <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{'comparison_'.$calculatedMetric.'_gbp'}) }}</td>
                                            <td class="px-4 py-3 text-right text-sm">{{ $money($report['totals']->{'variance_'.$calculatedMetric.'_gbp'}) }}</td>
                                        @endif
                                    </tr>
                                @endif
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
