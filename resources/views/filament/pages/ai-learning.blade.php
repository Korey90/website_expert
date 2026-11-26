<x-filament-panels::page>
    <section class="space-y-4 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
        <h2 class="text-lg font-semibold">{{ __('ai_assistant.learning') }}</h2>
        <p class="text-sm text-gray-500">{{ __('ai_assistant.learning_notice') }}</p>
        <label class="block max-w-xs text-sm">
            {{ __('ai_assistant.filter') }}
            <select wire:model.live="statusFilter" class="mt-1 w-full rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800">
                <option value="open">{{ __('ai_assistant.open') }}</option>
                <option value="reviewed">{{ __('ai_assistant.reviewed') }}</option>
                <option value="resolved">{{ __('ai_assistant.resolved') }}</option>
                <option value="all">{{ __('ai_assistant.all') }}</option>
            </select>
        </label>
        @forelse ($this->questions() as $question)
            <div wire:key="unanswered-{{ $question['id'] }}" class="rounded-lg border border-gray-200 p-4 dark:border-gray-700">
                <p class="whitespace-pre-wrap break-words text-sm">{{ $question['question'] }}</p>
                <div class="mt-2 flex flex-wrap items-center justify-between gap-3 text-xs text-gray-500">
                    <span>{{ $question['created_at'] }} · {{ $question['status'] }}</span>
                    <div class="flex flex-wrap gap-2">
                        <x-filament::button size="xs" color="gray" wire:click="mark('{{ $question['id'] }}', 'reviewed')">{{ __('ai_assistant.reviewed') }}</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="mark('{{ $question['id'] }}', 'resolved')">{{ __('ai_assistant.resolved') }}</x-filament::button>
                        <x-filament::button size="xs" color="gray" wire:click="mark('{{ $question['id'] }}', 'open')">{{ __('ai_assistant.reopen') }}</x-filament::button>
                    </div>
                </div>
            </div>
        @empty
            <p class="text-sm text-gray-500">{{ __('ai_assistant.empty_questions') }}</p>
        @endforelse
        <p class="border-t border-gray-200 pt-4 text-sm dark:border-gray-700">{{ __('ai_assistant.learning_manual') }}</p>
    </section>
</x-filament-panels::page>
