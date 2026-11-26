<x-filament-panels::page>
    @php($metrics = $this->metrics())
    <section class="space-y-5">
        <h2 class="text-xl font-semibold">{{ __('ai_assistant.analytics') }}</h2>
        <label class="block max-w-xs text-sm">{{ __('ai_assistant.period') }}
            <select wire:model.live="days" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                <option value="7">7 {{ __('ai_assistant.days') }}</option>
                <option value="30">30 {{ __('ai_assistant.days') }}</option>
                <option value="90">90 {{ __('ai_assistant.days') }}</option>
            </select>
        </label>
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                __('ai_assistant.conversations') => $metrics['conversations'],
                __('ai_assistant.answered') => $metrics['answered'],
                __('ai_assistant.unknown_count') => $metrics['unknown'],
                __('ai_assistant.unavailable_count') => $metrics['unavailable'],
                __('ai_assistant.unreviewed') => $metrics['unreviewed'],
                __('ai_assistant.ai_leads') => $metrics['leads'],
                __('ai_assistant.provider_calls') => $metrics['provider_calls'],
                __('ai_assistant.reserved_usd') => '$'.$metrics['reserved_usd'],
            ] as $label => $value)
                <div class="rounded-xl border border-gray-200 p-5 dark:border-gray-700">
                    <p class="text-sm text-gray-500">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold">{{ $value }}</p>
                </div>
            @endforeach
        </div>
        <p class="text-sm text-gray-500">{{ __('ai_assistant.usage_warning') }}</p>
    </section>
</x-filament-panels::page>
