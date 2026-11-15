<x-filament-panels::page>
    <div
        x-data="{ tab: 'steps' }"
        class="space-y-4"
    >
        {{-- Tab header --}}
        <div
            role="tablist"
            aria-label="{{ __('admin.migration.administration.calculator.tabs_label') }}"
            class="flex gap-1 overflow-x-auto border-b border-gray-200 pb-0 dark:border-gray-700"
        >
            <button
                type="button"
                role="tab"
                x-on:click="tab = 'steps'"
                x-bind:aria-selected="tab === 'steps'"
                x-bind:class="tab === 'steps'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="-mb-px shrink-0 whitespace-nowrap px-4 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                {{ __('admin.migration.administration.calculator.steps_tab') }}
            </button>
            <button
                type="button"
                role="tab"
                x-on:click="tab = 'strings'"
                x-bind:aria-selected="tab === 'strings'"
                x-bind:class="tab === 'strings'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="-mb-px shrink-0 whitespace-nowrap px-4 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                {{ __('admin.migration.administration.calculator.strings_tab') }}
            </button>
            <button
                type="button"
                role="tab"
                x-on:click="tab = 'pricing'"
                x-bind:aria-selected="tab === 'pricing'"
                x-bind:class="tab === 'pricing'
                    ? 'border-b-2 border-primary-600 text-primary-600 font-semibold'
                    : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 hover:border-b-2 hover:border-gray-300'"
                class="-mb-px shrink-0 whitespace-nowrap px-4 py-3 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-primary-600"
            >
                {{ __('admin.migration.administration.calculator.pricing_tab') }}
            </button>
        </div>

        {{-- Steps tab --}}
        <div x-show="tab === 'steps'" x-cloak role="tabpanel">
            <livewire:app.filament.widgets.calculator-steps-table-widget key="calc-steps" />
        </div>

        {{-- UI Strings tab --}}
        <div x-show="tab === 'strings'" x-cloak role="tabpanel">
            <livewire:app.filament.widgets.calculator-strings-table-widget key="calc-strings" />
        </div>

        {{-- Pricing tab --}}
        <div x-show="tab === 'pricing'" x-cloak role="tabpanel">
            <livewire:app.filament.widgets.calculator-pricing-table-widget key="calc-pricing" />
        </div>
    </div>
</x-filament-panels::page>
