<x-filament-panels::page>
    @unless ($this->canManageProfile())
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-700 dark:bg-amber-900/20 dark:text-amber-300">
            {{ __('finance.accounting.read_only') }}
        </div>
    @endunless

    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        @if ($this->canManageProfile())
            <x-filament::button type="submit" icon="heroicon-o-check">
                {{ __('finance.accounting.save') }}
            </x-filament::button>
        @endif
    </form>
</x-filament-panels::page>
