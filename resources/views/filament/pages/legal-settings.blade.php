<x-filament-panels::page>
    <div class="space-y-2 mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
            ⚠️ Legal document variables
        </p>
        <p class="text-xs text-amber-700 dark:text-amber-400">
            Values saved here are injected into all CMS legal documents using tokens like <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded">@{{legal.company_name}}</code>.
            Fill in all required fields before publishing legal documents.
        </p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="heroicon-o-check">
                Save settings
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
