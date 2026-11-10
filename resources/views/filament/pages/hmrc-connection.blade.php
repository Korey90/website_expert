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
                            <dd class="font-medium">{{ $fraudValidationSummary['code'] ?? '—' }}</dd>
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
                @endif
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
