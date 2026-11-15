<x-filament-panels::page>
    <div class="space-y-2 mb-4 p-4 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700">
        <p class="text-sm font-medium text-amber-800 dark:text-amber-300">
            {{ __('admin.migration.administration.settings.legal_notice_heading') }}
        </p>
        <p class="text-xs text-amber-700 dark:text-amber-400">
            {{ __('admin.migration.administration.settings.legal_notice_description') }}
            <code class="bg-amber-100 px-1 dark:bg-amber-900">@{{legal.company_name}}</code>
        </p>
    </div>

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('admin.migration.administration.settings.save') }}
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
