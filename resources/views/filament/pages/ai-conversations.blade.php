<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="space-y-3 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
            <h2 class="text-lg font-semibold">{{ __('ai_assistant.conversations') }}</h2>
            @forelse ($this->conversations() as $conversation)
                <button type="button" wire:key="conv-{{ $conversation['id'] }}"
                    wire:click="selectConversation('{{ $conversation['id'] }}')"
                    class="block w-full rounded-lg border border-gray-200 p-3 text-left text-sm hover:border-primary-500 dark:border-gray-700">
                    <div class="flex justify-between gap-2">
                        <span class="font-semibold">{{ \Illuminate\Support\Str::limit($conversation['id'], 12) }}</span>
                        <span class="text-gray-500">{{ strtoupper($conversation['locale']) }}</span>
                    </div>
                    <div class="mt-1 text-xs text-gray-500">
                        {{ $conversation['last_message_at'] ?? '—' }} · {{ $conversation['status'] }}
                        @if ($conversation['lead_id']) · CRM lead #{{ $conversation['lead_id'] }} @endif
                    </div>
                </button>
            @empty
                <p>{{ __('ai_assistant.empty_conversations') }}</p>
            @endforelse
        </section>
        <section class="space-y-3 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
            <h2 class="text-lg font-semibold">{{ __('ai_assistant.messages') }}</h2>
            @forelse ($this->selectedMessages() as $message)
                <div class="rounded-lg bg-gray-50 p-3 text-sm dark:bg-gray-800">
                    <div class="mb-1 flex justify-between text-xs text-gray-500">
                        <span>{{ $message['role'] }} · {{ $message['status'] }}</span>
                        <span>{{ $message['created_at'] }}</span>
                    </div>
                    <p class="whitespace-pre-wrap break-words">{{ $message['text'] }}</p>
                </div>
            @empty
                <p class="text-gray-500">{{ __('ai_assistant.choose_conversation') }}</p>
            @endforelse
        </section>
    </div>
</x-filament-panels::page>
