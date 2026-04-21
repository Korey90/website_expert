<x-filament-panels::page>

    {{-- Settings form --}}
    <form wire:submit.prevent="save" class="space-y-6">
        {{ $this->form }}

        <div>
            <x-filament::button type="submit" icon="heroicon-o-check">
                Save settings
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <x-slot name="heading">Navigation Links</x-slot>
        <x-slot name="description">Drag rows to reorder. Click "Add menu item" to create a new link.</x-slot>

        {{ $this->table }}
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-panels::page>
