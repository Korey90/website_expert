<x-filament-panels::page>
    @php($report = $this->getReport())

    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900 md:grid-cols-4">
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.bank_reconciliation_report.fields.date_from') }}</span>
                <input type="date" wire:model.blur="dateFrom" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.bank_reconciliation_report.fields.date_to') }}</span>
                <input type="date" wire:model.blur="dateTo" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
            </label>
            <label class="space-y-1 text-sm text-gray-700 dark:text-gray-300">
                <span>{{ __('finance.bank_reconciliation_report.fields.account') }}</span>
                <select wire:model.live="accountId" class="w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                    <option value="">{{ __('finance.bank_reconciliation_report.all_accounts') }}</option>
                    @foreach ($this->getAccountOptions() as $id => $label)
                        <option value="{{ $id }}">{{ $label }}</option>
                    @endforeach
                </select>
            </label>
            <div class="flex items-end">
                @include('filament.pages.partials.report-export-buttons')
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                'accounts' => $report['totals']->account_count,
                'transactions' => $report['totals']->transaction_count,
                'reconciled' => $report['totals']->reconciled_count,
                'open' => $report['totals']->open_count,
            ] as $label => $value)
                <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <div class="text-sm text-gray-500">{{ __('finance.bank_reconciliation_report.summary.'.$label) }}</div>
                    <div class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $value }}</div>
                </div>
            @endforeach
        </div>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('finance.bank_reconciliation_report.sections.accounts') }}</h2>
                <p class="mt-1 text-xs text-gray-500">{{ __('finance.bank_reconciliation_report.native_currency_notice') }}</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach (['account', 'opening_balance', 'inflows', 'outflows', 'closing_balance', 'allocated_amount', 'remaining_amount', 'reconciliation_rate', 'transactions'] as $column)
                                <th class="px-4 py-3 {{ $column === 'account' ? 'text-left' : 'text-right' }} text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.bank_reconciliation_report.columns.'.$column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['accounts'] as $account)
                            <tr>
                                <td class="px-4 py-3 text-sm">
                                    <div class="font-medium text-gray-900 dark:text-white">{{ $account->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $account->bank_name }} · {{ $account->masked_account_number }} · {{ $account->currency }}</div>
                                    @if ($account->coverage_start)
                                        <div class="text-xs text-gray-500">{{ __('finance.bank_reconciliation_report.coverage_from', ['date' => $account->coverage_start]) }}</div>
                                    @endif
                                </td>
                                @foreach (['opening_balance', 'inflows', 'outflows', 'closing_balance', 'allocated_amount', 'remaining_amount'] as $column)
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-sm {{ in_array($column, ['closing_balance', 'remaining_amount'], true) ? 'font-semibold text-gray-900 dark:text-white' : 'text-gray-600 dark:text-gray-300' }}">
                                        {{ \App\Filament\Support\Currency::format($account->{$column}, $account->currency) }}
                                    </td>
                                @endforeach
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ number_format($account->reconciliation_rate, 1) }}%</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ $account->transaction_count }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('finance.bank_reconciliation_report.empty_accounts') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-gray-700 dark:bg-gray-800">
                <h2 class="font-semibold text-gray-900 dark:text-white">{{ __('finance.bank_reconciliation_report.sections.transactions') }}</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-800">
                        <tr>
                            @foreach (['date', 'account', 'reference', 'counterparty', 'amount', 'allocated_amount', 'remaining_amount', 'status'] as $column)
                                <th class="px-4 py-3 {{ in_array($column, ['amount', 'allocated_amount', 'remaining_amount'], true) ? 'text-right' : 'text-left' }} text-xs font-medium uppercase tracking-wider text-gray-500">{{ __('finance.bank_reconciliation_report.columns.'.$column) }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                        @forelse ($report['transactions'] as $transaction)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->booking_date }}</td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->account_name }}</td>
                                <td class="min-w-48 px-4 py-3 text-sm font-medium">
                                    <a class="text-primary-600 hover:underline" href="{{ \App\Filament\Resources\BankTransactionResource::getUrl('view', ['record' => $transaction->id]) }}">
                                        {{ $transaction->reference ?: '—' }}
                                    </a>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ $transaction->counterparty ?: '—' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900 dark:text-white">{{ \App\Filament\Support\Currency::format($transaction->amount, $transaction->currency) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ \App\Filament\Support\Currency::format($transaction->allocated_amount, $transaction->currency) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600 dark:text-gray-300">{{ \App\Filament\Support\Currency::format($transaction->remaining_amount, $transaction->currency) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">{{ __('finance.bank_reconciliation.statuses.'.$transaction->status) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-6 py-10 text-center text-sm text-gray-500">{{ __('finance.bank_reconciliation_report.empty_transactions') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-filament-panels::page>
