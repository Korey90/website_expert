<x-filament-panels::page>
    @php($report = $this->getReport())

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-3">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.general_ledger.fields.date_from') }}</span>
                <input type="date" wire:model.blur="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.general_ledger.fields.date_to') }}</span>
                <input type="date" wire:model.blur="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.general_ledger.fields.account') }}</span>
                <select wire:model.live="accountId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.general_ledger.all_accounts') }}</option>
                    @foreach ($this->getAccountOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach (['debit_gbp', 'credit_gbp', 'difference_gbp'] as $field)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-sm text-gray-500">{{ __('finance.general_ledger.columns.'.$field) }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">£{{ number_format($report['totals']->{$field}, 2) }}</div>
                </div>
            @endforeach
            <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="text-sm text-gray-500">{{ __('finance.general_ledger.columns.lines') }}</div>
                <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $report['totals']->line_count }}</div>
            </div>
        </div>

        @forelse ($report['accounts'] as $account)
            <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                    <div>
                        <h2 class="font-semibold text-gray-900 dark:text-white">{{ $account->code }} — {{ $account->name }}</h2>
                        <p class="text-xs text-gray-500">{{ __('finance.general_ledger.normal_balance') }}: {{ __('finance.general_ledger.sides.'.$account->normal_balance) }}</p>
                    </div>
                    <div class="flex gap-6 text-sm">
                        <div>
                            <span class="text-gray-500">{{ __('finance.general_ledger.opening_balance') }}</span>
                            <span class="ml-2 font-semibold text-gray-900 dark:text-white">£{{ number_format($account->opening_balance->amount, 2) }} {{ __('finance.general_ledger.sides_short.'.$account->opening_balance->side) }}</span>
                        </div>
                        <div>
                            <span class="text-gray-500">{{ __('finance.general_ledger.closing_balance') }}</span>
                            <span class="ml-2 font-semibold text-gray-900 dark:text-white">£{{ number_format($account->closing_balance->amount, 2) }} {{ __('finance.general_ledger.sides_short.'.$account->closing_balance->side) }}</span>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-800">
                            <tr>
                                @foreach (['date', 'journal', 'description', 'dimensions', 'debit_gbp', 'credit_gbp', 'balance'] as $column)
                                    <th class="px-4 py-3 {{ in_array($column, ['debit_gbp', 'credit_gbp', 'balance'], true) ? 'text-right' : 'text-left' }} text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.general_ledger.columns.'.$column) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse ($account->rows as $row)
                                <tr>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $row->entry_date }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm font-medium">
                                        <a class="text-primary-600 hover:underline" href="{{ \App\Filament\Resources\JournalEntryResource::getUrl('view', ['record' => $row->entry_id]) }}">{{ $row->entry_number }}</a>
                                    </td>
                                    <td class="min-w-64 px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <div>{{ $row->description }}</div>
                                        @if ($row->memo)<div class="text-xs text-gray-500">{{ $row->memo }}</div>@endif
                                    </td>
                                    <td class="px-4 py-3 text-xs text-gray-500">
                                        @foreach (array_filter([$row->client, $row->supplier, $row->project, $row->tax_code]) as $dimension)
                                            <div>{{ $dimension }}</div>
                                        @endforeach
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $row->debit_gbp > 0 ? '£'.number_format($row->debit_gbp, 2) : '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $row->credit_gbp > 0 ? '£'.number_format($row->credit_gbp, 2) : '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">£{{ number_format($row->balance->amount, 2) }} {{ __('finance.general_ledger.sides_short.'.$row->balance->side) }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-6 py-8 text-center text-sm text-gray-500">{{ __('finance.general_ledger.no_period_activity') }}</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 font-semibold dark:bg-gray-800">
                            <tr>
                                <td colspan="4" class="px-4 py-3 text-right text-sm text-gray-700 dark:text-gray-300">{{ __('finance.general_ledger.period_totals') }}</td>
                                <td class="px-4 py-3 text-right text-sm">£{{ number_format($account->debit_gbp, 2) }}</td>
                                <td class="px-4 py-3 text-right text-sm">£{{ number_format($account->credit_gbp, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </section>
        @empty
            <div class="rounded-xl border border-gray-200 bg-white px-6 py-12 text-center text-sm text-gray-500 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                {{ __('finance.general_ledger.empty') }}
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
