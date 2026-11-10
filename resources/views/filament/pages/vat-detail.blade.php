<x-filament-panels::page>
    @php($workspace = $this->getWorkspace())
    @php($report = $workspace['vat_detail'] ?? null)
    @php($vatReturn = $workspace)
    @php($money = fn (float $value): string => ($value < 0 ? '-£' : '£').number_format(abs($value), 2))

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-[1fr_1fr_auto] md:items-end">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.vat_detail.fields.date_from') }}</span>
                <input type="date" wire:model.live="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.vat_detail.fields.date_to') }}</span>
                <input type="date" wire:model.live="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            @if ($this->getExportUrl())
                <a href="{{ $this->getExportUrl() }}" class="fi-btn fi-btn-color-primary inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-semibold text-white">
                    {{ __('finance.vat_detail.export_csv') }}
                </a>
            @endif
        </div>

        @if (! $report)
            <div class="rounded-xl border border-dashed border-gray-300 p-10 text-center text-sm text-gray-500 dark:border-gray-700">
                {{ __('finance.vat_detail.select_dates') }}
            </div>
        @else
            <div class="rounded-xl border border-info-200 bg-info-50 p-4 text-sm text-info-800 dark:border-info-900 dark:bg-info-950/40 dark:text-info-200">
                <div class="font-semibold">{{ __('finance.vat_detail.period_label', ['from' => $report['date_from'], 'to' => $report['date_to']]) }}</div>
                <div class="mt-1">{{ __('finance.vat_detail.methodology') }}</div>
                <div class="mt-1 font-medium">{{ __('finance.vat_detail.disclaimer') }}</div>
            </div>

            <div class="flex flex-wrap items-start justify-between gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('finance.vat_detail.summary_title') }}</h2>
                    <p class="mt-1 text-sm text-gray-500">
                        {{ __('finance.vat_detail.summary', [
                            'rows' => $report['totals']->row_count,
                            'documents' => $report['totals']->document_count,
                            'journals' => $report['totals']->journal_count,
                            'adjustments' => $report['totals']->adjustment_count,
                        ]) }}
                    </p>
                </div>
                <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $report['totals']->is_reconciled ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300' }}">
                    {{ __('finance.vat_detail.'.($report['totals']->is_reconciled ? 'reconciled' : 'unreconciled')) }}
                </span>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
                @foreach (['net_sales', 'net_purchases', 'output_vat', 'input_vat', 'net_vat'] as $metric)
                    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <div class="text-xs font-medium uppercase tracking-wide text-gray-500">{{ __('finance.vat_detail.metrics.'.$metric) }}</div>
                        <div class="mt-2 text-xl font-semibold {{ $report['totals']->{$metric.'_gbp'} < 0 ? 'text-danger-600' : 'text-gray-900 dark:text-white' }}">
                            {{ $money($report['totals']->{$metric.'_gbp'}) }}
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-4 md:grid-cols-2">
                @foreach (['output', 'input'] as $side)
                    <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                        <h3 class="font-semibold text-gray-900 dark:text-white">{{ __('finance.vat_detail.reconciliation.'.$side) }}</h3>
                        <dl class="mt-3 grid grid-cols-3 gap-3 text-sm">
                            <div>
                                <dt class="text-gray-500">{{ __('finance.vat_detail.metrics.'.$side.'_vat') }}</dt>
                                <dd class="mt-1 font-semibold">{{ $money($report['totals']->{$side.'_vat_gbp'}) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('finance.vat_detail.metrics.'.$side.'_control') }}</dt>
                                <dd class="mt-1 font-semibold">{{ $money($report['totals']->{$side.'_control_gbp'}) }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500">{{ __('finance.vat_detail.metrics.'.$side.'_difference') }}</dt>
                                <dd class="mt-1 font-semibold {{ $report['totals']->{$side.'_difference_gbp'} == 0.0 ? 'text-success-600' : 'text-danger-600' }}">
                                    {{ $money($report['totals']->{$side.'_difference_gbp'}) }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                @endforeach
            </div>

            <section class="space-y-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white">{{ __('finance.vat_return_calculation.title') }}</h2>
                        <p class="mt-1 max-w-3xl text-sm text-gray-500">{{ __('finance.vat_return_calculation.subtitle') }}</p>
                    </div>
                    <span class="inline-flex rounded-full px-3 py-1 text-sm font-semibold {{ $vatReturn['status']->is_ready_for_review ? 'bg-success-50 text-success-700 dark:bg-success-950/40 dark:text-success-300' : 'bg-danger-50 text-danger-700 dark:bg-danger-950/40 dark:text-danger-300' }}">
                        {{ __('finance.vat_return_calculation.'.($vatReturn['status']->is_ready_for_review ? 'ready' : 'blocked')) }}
                    </span>
                </div>

                <div class="rounded-lg border border-warning-200 bg-warning-50 p-3 text-sm font-medium text-warning-800 dark:border-warning-900 dark:bg-warning-950/40 dark:text-warning-200">
                    {{ __('finance.vat_return_calculation.not_submission') }}
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vatReturn['boxes'] as $box)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">{{ __('finance.vat_return_calculation.box_label', ['box' => $box->number]) }}</div>
                                    <div class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ __('finance.vat_return_calculation.boxes.'.$box->number) }}</div>
                                </div>
                                @if ($box->is_derived)
                                    <span class="rounded bg-info-50 px-2 py-0.5 text-xs text-info-700 dark:bg-info-950/40 dark:text-info-300">{{ __('finance.vat_return_calculation.derived') }}</span>
                                @endif
                            </div>
                            <div class="mt-3 text-2xl font-semibold text-gray-900 dark:text-white">
                                {{ $box->decimal_places === 2 ? $money($box->submission_value) : '£'.number_format($box->submission_value, 0) }}
                            </div>
                            <div class="mt-1 font-mono text-xs text-gray-400">{{ $box->hmrc_field }}</div>
                            @if ($box->decimal_places === 0 && $box->raw_gbp != $box->submission_value)
                                <div class="mt-2 text-xs text-gray-500">
                                    {{ __('finance.vat_return_calculation.raw_ledger_value', ['value' => $money($box->raw_gbp)]) }}
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="rounded-lg bg-gray-50 p-4 text-sm dark:bg-gray-800">
                    <span class="text-gray-500">{{ __('finance.vat_return_calculation.net_position') }}</span>
                    <span class="ml-2 font-semibold text-gray-900 dark:text-white">
                        {{ __('finance.vat_return_calculation.directions.'.$vatReturn['status']->net_direction) }} — {{ $money(abs($vatReturn['status']->signed_net_vat_gbp)) }}
                    </span>
                </div>

                @if ($vatReturn['issues']->isNotEmpty())
                    <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-sm text-danger-800 dark:border-danger-900 dark:bg-danger-950/40 dark:text-danger-200">
                        <div class="font-semibold">{{ __('finance.vat_return_calculation.issues_title') }}</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($vatReturn['issues'] as $issue)
                                <li>{{ __('finance.vat_return_calculation.issues.'.$issue->code, $issue->context) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @if ($vatReturn['warnings']->isNotEmpty())
                    <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-sm text-warning-800 dark:border-warning-900 dark:bg-warning-950/40 dark:text-warning-200">
                        <div class="font-semibold">{{ __('finance.vat_return_calculation.warnings_title') }}</div>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            @foreach ($vatReturn['warnings'] as $warning)
                                <li>{{ __('finance.vat_return_calculation.warnings.'.$warning->code, $warning->context) }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('finance.vat_detail.tax_code_summary') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                @foreach (['tax_code', 'type', 'rate', 'boxes', 'net_sales', 'net_purchases', 'output_vat', 'input_vat'] as $column)
                                    <th class="whitespace-nowrap px-4 py-3 {{ in_array($column, ['net_sales', 'net_purchases', 'output_vat', 'input_vat'], true) ? 'text-right' : '' }}">{{ __('finance.vat_detail.columns.'.$column) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['tax_codes'] as $taxCode)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 font-semibold text-gray-900 dark:text-white">
                                        {{ $taxCode->code }} · {{ $taxCode->label }}
                                        @if (! $taxCode->is_active)
                                            <span class="ml-1 rounded bg-gray-100 px-2 py-0.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">{{ __('finance.vat_detail.inactive') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3">{{ __('finance.vat_detail.tax_types.'.$taxCode->type) }}</td>
                                    <td class="px-4 py-3">{{ $taxCode->rate !== null ? number_format($taxCode->rate, 2).'%' : '—' }}</td>
                                    <td class="px-4 py-3">{{ $taxCode->mapping_boxes === [] ? '—' : implode(', ', $taxCode->mapping_boxes) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($taxCode->net_sales_gbp) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($taxCode->net_purchases_gbp) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($taxCode->output_vat_gbp) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right">{{ $money($taxCode->input_vat_gbp) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">{{ __('finance.vat_detail.no_tax_codes') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-200 px-5 py-4 dark:border-gray-700">
                    <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('finance.vat_detail.document_detail') }}</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr class="text-left text-xs uppercase tracking-wide text-gray-500">
                                @foreach (['tax_point', 'document_type', 'reference', 'counterparty', 'currency', 'tax_code', 'net_sales', 'net_purchases', 'output_vat', 'input_vat'] as $column)
                                    <th class="whitespace-nowrap px-3 py-3 {{ in_array($column, ['net_sales', 'net_purchases', 'output_vat', 'input_vat'], true) ? 'text-right' : '' }}">{{ __('finance.vat_detail.columns.'.$column) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($report['rows'] as $row)
                                <tr class="text-gray-700 dark:text-gray-300">
                                    <td class="whitespace-nowrap px-3 py-3">{{ $row->tax_point }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        {{ __('finance.vat_detail.document_types.'.$row->kind) }}
                                        @if ($row->is_adjustment)
                                            <span class="ml-1 rounded bg-warning-50 px-2 py-0.5 text-xs text-warning-700 dark:bg-warning-950/40 dark:text-warning-300">{{ __('finance.vat_detail.adjustment') }}</span>
                                        @endif
                                        @if ($row->is_reversal)
                                            <span class="ml-1 rounded bg-danger-50 px-2 py-0.5 text-xs text-danger-700 dark:bg-danger-950/40 dark:text-danger-300">{{ __('finance.vat_detail.reversal') }}</span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3 font-medium text-gray-900 dark:text-white">{{ $row->reference }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $row->counterparty }}</td>
                                    <td class="px-3 py-3">{{ $row->currency }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $row->tax_code }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $money($row->net_sales_gbp) }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $money($row->net_purchases_gbp) }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $money($row->output_vat_gbp) }}</td>
                                    <td class="whitespace-nowrap px-3 py-3 text-right">{{ $money($row->input_vat_gbp) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="10" class="px-3 py-8 text-center text-gray-500">{{ __('finance.vat_detail.no_rows') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-filament-panels::page>
