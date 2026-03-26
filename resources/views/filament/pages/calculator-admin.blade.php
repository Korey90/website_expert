<x-filament-panels::page>
    <div
        x-data="{ tab: 'steps' }"
        class="space-y-4"
    >
        {{-- Tab header --}}
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700 pb-0">
            <button
                type="button"
                x-on:click="tab = 'steps'"
                x-bind:class="tab === 'steps'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="px-5 py-3 text-sm transition-colors -mb-px"
            >
                📋 Steps
            </button>
            <button
                type="button"
                x-on:click="tab = 'strings'"
                x-bind:class="tab === 'strings'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="px-5 py-3 text-sm transition-colors -mb-px"
            >
                🌐 UI Strings
            </button>
            <button
                type="button"
                x-on:click="tab = 'pricing'"
                x-bind:class="tab === 'pricing'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="px-5 py-3 text-sm transition-colors -mb-px"
            >
                💰 Pricing
            </button>
        </div>

        {{-- Steps tab --}}
        <div x-show="tab === 'steps'" x-cloak>
            <livewire:app.filament.widgets.calculator-steps-table-widget key="calc-steps" />
        </div>

        {{-- UI Strings tab --}}
        <div x-show="tab === 'strings'" x-cloak>
            <livewire:app.filament.widgets.calculator-strings-table-widget key="calc-strings" />
        </div>

        {{-- Pricing tab --}}
        <div x-show="tab === 'pricing'" x-cloak>
            <livewire:app.filament.widgets.calculator-pricing-table-widget key="calc-pricing" />
        </div>
    </div>
</x-filament-panels::page>
