<x-filament-panels::page>
    @php($summary = $this->getConnectionSummary())

    <div class="grid gap-6 lg:grid-cols-2">
        <x-filament::section>
            <x-slot name="heading">{{ __('finance.hmrc_connection.sections.status') }}</x-slot>
            <x-slot name="description">{{ __('finance.hmrc_connection.subtitle') }}</x-slot>

            <dl class="grid gap-4 text-sm sm:grid-cols-2">
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.environment') }}</dt>
                    <dd class="font-medium uppercase">{{ $summary['environment'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.status') }}</dt>
                    <dd class="font-medium">{{ $summary['status_label'] }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.vrn') }}</dt>
                    <dd class="font-mono font-medium">{{ $summary['masked_vrn'] ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.scopes') }}</dt>
                    <dd class="font-medium">{{ implode(', ', $summary['scopes']) ?: '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.connected_at') }}</dt>
                    <dd>{{ $summary['connected_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.expires_at') }}</dt>
                    <dd>{{ $summary['expires_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fields.refreshed_at') }}</dt>
                    <dd>{{ $summary['refreshed_at']?->timezone(config('app.timezone'))->format('Y-m-d H:i:s') ?? '—' }}</dd>
                </div>
            </dl>

            @if ($summary['reconnect_reason'])
                <div class="mt-5 rounded-lg bg-warning-50 p-4 text-sm text-warning-800 dark:bg-warning-950 dark:text-warning-200">
                    {{ __('finance.hmrc_connection.help.reconnect_required') }}
                </div>
            @endif
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.hmrc_connection.sections.security') }}</x-slot>

            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <p>{{ __('finance.hmrc_connection.help.security') }}</p>
                <p>{{ __('finance.hmrc_connection.help.external_revoke') }}</p>
                <p class="font-medium text-danger-700 dark:text-danger-300">{{ __('finance.hmrc_connection.help.sandbox_only') }}</p>
            </div>
        </x-filament::section>

        <x-filament::section>
            <x-slot name="heading">{{ __('finance.hmrc_connection.sections.fraud_prevention') }}</x-slot>
            <x-slot name="description">{{ __('finance.hmrc_connection.fraud_validation.description') }}</x-slot>

            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-300">
                <p>{{ __('finance.hmrc_connection.fraud_validation.browser_notice') }}</p>
                <p>{{ __('finance.hmrc_connection.fraud_validation.infrastructure_notice') }}</p>

                @if ($fraudValidationSummary)
                    <dl class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.code') }}</dt>
                            <dd @class([
                                'font-medium',
                                'text-success-700 dark:text-success-300' => $fraudValidationSummary['valid'] ?? false,
                                'text-danger-700 dark:text-danger-300' => ! ($fraudValidationSummary['valid'] ?? false),
                            ])>{{ $fraudValidationSummary['code'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.spec_version') }}</dt>
                            <dd class="font-medium">{{ $fraudValidationSummary['spec_version'] ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.errors') }}</dt>
                            <dd class="font-medium">{{ $fraudValidationSummary['errors'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.warnings') }}</dt>
                            <dd class="font-medium">{{ $fraudValidationSummary['warnings'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.checked_at') }}</dt>
                            <dd class="font-medium">{{ $fraudValidationSummary['checked_at'] }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.correlation_id') }}</dt>
                            <dd class="font-mono font-medium">{{ $fraudValidationSummary['correlation_id'] ?? '—' }}</dd>
                        </div>
                    </dl>

                    @if (! empty($fraudValidationSummary['error_items']))
                        <div class="rounded-lg border border-danger-200 bg-danger-50 p-4 text-danger-900 dark:border-danger-800 dark:bg-danger-950/40 dark:text-danger-100">
                            <p class="font-semibold">{{ __('finance.hmrc_connection.fraud_validation.error_details') }}</p>
                            <ul class="mt-2 list-disc space-y-2 pl-5">
                                @foreach ($fraudValidationSummary['error_items'] as $issue)
                                    <li>
                                        <span class="font-mono">{{ implode(', ', $issue['headers']) ?: '—' }}</span>
                                        <span>· {{ $issue['code'] ?? 'UNKNOWN' }}</span>
                                        @if ($issue['message'])
                                            <span>· {{ $issue['message'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    @if (! empty($fraudValidationSummary['warning_items']))
                        <div class="rounded-lg border border-warning-200 bg-warning-50 p-4 text-warning-900 dark:border-warning-800 dark:bg-warning-950/40 dark:text-warning-100">
                            <p class="font-semibold">{{ __('finance.hmrc_connection.fraud_validation.warning_details') }}</p>
                            <ul class="mt-2 list-disc space-y-2 pl-5">
                                @foreach ($fraudValidationSummary['warning_items'] as $issue)
                                    <li>
                                        <span class="font-mono">{{ implode(', ', $issue['headers']) ?: '—' }}</span>
                                        <span>· {{ $issue['code'] ?? 'UNKNOWN' }}</span>
                                        @if ($issue['message'])
                                            <span>· {{ $issue['message'] }}</span>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @endif

                @if ($fraudFeedbackSummary)
                    <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                        <p class="font-semibold">{{ __('finance.hmrc_connection.fraud_feedback.title') }}</p>
                        <dl class="mt-3 grid gap-3 sm:grid-cols-3">
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_feedback.request_count') }}</dt>
                                <dd class="font-medium">{{ $fraudFeedbackSummary['request_count'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.checked_at') }}</dt>
                                <dd class="font-medium">{{ $fraudFeedbackSummary['checked_at'] }}</dd>
                            </div>
                            <div>
                                <dt class="text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.fraud_validation.correlation_id') }}</dt>
                                <dd class="font-mono font-medium">{{ $fraudFeedbackSummary['correlation_id'] ?? '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                @endif
            </div>
        </x-filament::section>

        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">{{ __('finance.hmrc_connection.obligations.title') }}</x-slot>
            <x-slot name="description">{{ __('finance.hmrc_connection.obligations.description') }}</x-slot>

            @php($obligations = $this->getVatObligations())

            @if ($obligations->isEmpty())
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('finance.hmrc_connection.obligations.empty') }}
                </p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                            <tr>
                                <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.obligations.period') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.obligations.due') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.obligations.status') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.obligations.received') }}</th>
                                <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.obligations.synced_at') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                            @foreach ($obligations as $obligation)
                                <tr>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        {{ $obligation->start_date->format('Y-m-d') }} – {{ $obligation->end_date->format('Y-m-d') }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $obligation->due_date->format('Y-m-d') }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        <x-filament::badge :color="$obligation->status === 'open' ? 'warning' : 'success'">
                                            {{ __('finance.hmrc_connection.obligations.statuses.'.$obligation->status) }}
                                        </x-filament::badge>
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-3">{{ $obligation->received_date?->format('Y-m-d') ?? '—' }}</td>
                                    <td class="whitespace-nowrap px-3 py-3">
                                        {{ $obligation->last_seen_at->timezone(config('app.timezone'))->format('Y-m-d H:i:s') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <p class="mt-4 text-xs text-gray-500 dark:text-gray-400">
                {{ __('finance.hmrc_connection.obligations.period_key_notice') }}
            </p>
        </x-filament::section>

        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">{{ __('finance.hmrc_connection.read.return_title') }}</x-slot>
            <x-slot name="description">{{ __('finance.hmrc_connection.read.return_description') }}</x-slot>

            @if (! $vatReturnView)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('finance.hmrc_connection.read.return_empty') }}
                </p>
            @else
                <div class="mb-4 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('finance.hmrc_connection.read.period') }}: <strong>{{ $vatReturnView['period'] }}</strong></span>
                    <span>{{ __('finance.hmrc_connection.read.fetched_at') }}: <strong>{{ $vatReturnView['fetched_at'] }}</strong></span>
                    <span>{{ __('finance.hmrc_connection.read.correlation_id') }}: <strong class="font-mono">{{ $vatReturnView['correlation_id'] ?? '—' }}</strong></span>
                </div>

                <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($vatReturnView['boxes'] as $box => $amount)
                        <div class="rounded-lg border border-gray-200 p-4 dark:border-white/10">
                            <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">
                                {{ __('finance.hmrc_connection.read.box', ['box' => $box]) }}
                            </p>
                            <p class="mt-1 text-lg font-semibold tabular-nums">
                                {{ number_format($amount, $box <= 5 ? 2 : 0, '.', ',') }}
                            </p>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        <x-filament::section class="lg:col-span-2">
            <x-slot name="heading">{{ __('finance.hmrc_connection.read.account_title') }}</x-slot>
            <x-slot name="description">{{ __('finance.hmrc_connection.read.account_description') }}</x-slot>

            @if (! $vatAccountView)
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    {{ __('finance.hmrc_connection.read.account_empty') }}
                </p>
            @else
                <div class="mb-5 flex flex-wrap gap-x-6 gap-y-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>{{ __('finance.hmrc_connection.read.fetched_at') }}: <strong>{{ $vatAccountView['fetched_at'] }}</strong></span>
                    <span>{{ __('finance.hmrc_connection.read.liabilities_correlation_id') }}: <strong class="font-mono">{{ $vatAccountView['liabilities_correlation_id'] ?? '—' }}</strong></span>
                    <span>{{ __('finance.hmrc_connection.read.payments_correlation_id') }}: <strong class="font-mono">{{ $vatAccountView['payments_correlation_id'] ?? '—' }}</strong></span>
                </div>

                <h3 class="mb-3 font-semibold">{{ __('finance.hmrc_connection.read.liabilities') }}</h3>
                @if (empty($vatAccountView['liabilities']))
                    <p class="mb-6 text-sm text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.read.no_liabilities') }}</p>
                @else
                    <div class="mb-6 overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.read.tax_period') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.read.type') }}</th>
                                    <th class="px-3 py-2 text-right font-medium">{{ __('finance.hmrc_connection.read.original_amount') }}</th>
                                    <th class="px-3 py-2 text-right font-medium">{{ __('finance.hmrc_connection.read.outstanding_amount') }}</th>
                                    <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.read.due') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($vatAccountView['liabilities'] as $liability)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-3">{{ $liability['from'] }} – {{ $liability['to'] }}</td>
                                        <td class="px-3 py-3">{{ $liability['type'] }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">£{{ number_format($liability['original_amount'], 2, '.', ',') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">{{ $liability['outstanding_amount'] === null ? '—' : '£'.number_format($liability['outstanding_amount'], 2, '.', ',') }}</td>
                                        <td class="whitespace-nowrap px-3 py-3">{{ $liability['due'] ?? '—' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                <h3 class="mb-3 font-semibold">{{ __('finance.hmrc_connection.read.payments') }}</h3>
                @if (empty($vatAccountView['payments']))
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('finance.hmrc_connection.read.no_payments') }}</p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="border-b border-gray-200 text-gray-500 dark:border-white/10 dark:text-gray-400">
                                <tr>
                                    <th class="px-3 py-2 font-medium">{{ __('finance.hmrc_connection.read.received') }}</th>
                                    <th class="px-3 py-2 text-right font-medium">{{ __('finance.hmrc_connection.read.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                                @foreach ($vatAccountView['payments'] as $payment)
                                    <tr>
                                        <td class="whitespace-nowrap px-3 py-3">{{ $payment['received'] ?? '—' }}</td>
                                        <td class="whitespace-nowrap px-3 py-3 text-right tabular-nums">£{{ number_format($payment['amount'], 2, '.', ',') }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            @endif
        </x-filament::section>
    </div>
</x-filament-panels::page>
