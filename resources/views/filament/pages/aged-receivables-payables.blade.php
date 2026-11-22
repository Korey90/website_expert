<x-filament-panels::page>
    @php($report = $this->getReport())
    @php($money = fn (float $value): string => ($value < 0 ? '-£' : '£').number_format(abs($value), 2))

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.aged_receivables_payables.fields.as_of') }}</span>
                <input type="date" wire:model.live="asOf" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            @if ($this->getExportUrl())
                @include('filament.pages.partials.report-export-buttons')
            @endif
        </div>

        @if (! $report)
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">
                {{ __('finance.aged_receivables_payables.select_date') }}
            </div>
        @else
            <div class="rounded-xl border border-info-200 bg-info-50 p-4 text-sm text-info-800 dark:border-info-900 dark:bg-info-950/40 dark:text-info-200">
                <div class="font-semibold">{{ __('finance.aged_receivables_payables.as_of_label', ['date' => $report['as_of']]) }}</div>
                <div class="mt-1">{{ __('finance.aged_receivables_payables.methodology') }}</div>
            </div>

            @foreach ($report['sections'] as $section)
                <section class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                                {{ __('finance.aged_receivables_payables.sections.'.$section->key) }}
                            </h2>
                            <p class="mt-1 text-sm text-gray-500">
                                {{ __('finance.aged_receivables_payables.summary', [
                                    'documents' => $section->totals->document_count,
                                    'counterparties' => $section->totals->counterparty_count,
                                    'adjustments' => $section->totals->adjustment_count,
                                ]) }}
                            </p>
                        </div>
                        <div class="text-right text-sm text-gray-500">
                            <span class="inline-flex rounded-full px-3 py-1 font-semibold {{ $section->totals->is_reconciled ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300' }}">
                                {{ __('finance.aged_receivables_payables.'.($section->totals->is_reconciled ? 'reconciled' : 'unreconciled')) }}
                            </span>
                            <div class="mt-2">{{ __('finance.aged_receivables_payables.control_accounts') }}: {{ $section->totals->control_account_count }}</div>
                            <div>{{ __('finance.aged_receivables_payables.journals') }}: {{ $section->totals->journal_count }}</div>
                        </div>
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                        @foreach ($report['buckets'] as $bucket)
                            <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                                <div class="text-xs font-medium uppercase tracking-wide text-gray-500">
                                    {{ __('finance.aged_receivables_payables.buckets.'.$bucket) }}
                                </div>
                                <div class="mt-2 text-xl font-semibold {{ $section->totals->{$bucket.'_gbp'} < 0 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                                    {{ $money($section->totals->{$bucket.'_gbp'}) }}
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('finance.aged_receivables_payables.metrics.total') }}</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $money($section->totals->total_gbp) }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('finance.aged_receivables_payables.metrics.control_balance') }}</div>
                            <div class="mt-1 text-lg font-semibold text-gray-900 dark:text-white">{{ $money($section->totals->control_balance_gbp) }}</div>
                        </div>
                        <div class="rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                            <div class="text-xs uppercase tracking-wide text-gray-500">{{ __('finance.aged_receivables_payables.metrics.difference') }}</div>
                            <div class="mt-1 text-lg font-semibold {{ $section->totals->difference_gbp == 0.0 ? 'text-success-600' : 'text-danger-600' }}">{{ $money($section->totals->difference_gbp) }}</div>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                            <thead>
                                <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                    @foreach (['counterparty', 'reference', 'issue_date', 'due_date', 'currency', 'days_overdue', 'bucket', 'balance'] as $column)
                                        <th class="px-3 py-3 {{ $column === 'balance' ? 'text-right' : '' }}">{{ __('finance.aged_receivables_payables.columns.'.$column) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @forelse ($section->rows as $row)
                                    <tr class="text-gray-700 dark:text-gray-300">
                                        <td class="whitespace-nowrap px-3 py-3 font-medium text-gray-900 dark:text-white">{{ $row->counterparty }}</td>
                                        <td class="whitespace-nowrap px-3 py-3">
                                            {{ $row->reference }}
                                            @if ($row->is_adjustment)
                                                <span class="ml-1 rounded bg-warning-50 px-2 py-0.5 text-xs text-warning-700 dark:bg-warning-950/40 dark:text-warning-300">
                                                    {{ __('finance.aged_receivables_payables.adjustment') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap px-3 py-3">{{ $row->issue_date }}</td>
                                        <td class="whitespace-nowrap px-3 py-3">{{ $row->due_date }}</td>
                                        <td class="px-3 py-3">{{ $row->currency }}</td>
                                        <td class="px-3 py-3">{{ $row->days_overdue }}</td>
                                        <td class="whitespace-nowrap px-3 py-3">{{ __('finance.aged_receivables_payables.buckets.'.$row->bucket) }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right font-semibold {{ $row->balance_gbp < 0 ? 'text-danger-600' : '' }}">{{ $money($row->balance_gbp) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="px-3 py-8 text-center text-gray-500">{{ __('finance.aged_receivables_payables.no_rows') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </section>
            @endforeach
        @endif
    </div>
</x-filament-panels::page>
