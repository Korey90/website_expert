<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">Quick Actions</x-slot>

        <div class="flex flex-wrap gap-3">
            @foreach($this->getActions() as $action)
                <a
                    href="{{ $action['url'] }}"
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2.5 text-sm font-semibold shadow-sm ring-1 ring-inset transition-all
                        {{ $action['color'] === 'primary'
                            ? 'bg-primary-600 text-white hover:bg-primary-500 ring-primary-600'
                            : 'bg-white text-gray-700 hover:bg-gray-50 ring-gray-300 dark:bg-gray-800 dark:text-gray-200 dark:ring-gray-600 dark:hover:bg-gray-700' }}"
                >
                    <x-filament::icon
                        :icon="$action['icon']"
                        class="h-4 w-4"
                    />
                    {{ $action['label'] }}
                </a>
            @endforeach
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
