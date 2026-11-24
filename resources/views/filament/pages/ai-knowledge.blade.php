<x-filament-panels::page>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="space-y-4 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold">{{ __('ai_assistant.knowledge') }}</h2>
                @if (\App\Services\AI\AiAccess::allows('manage_ai_knowledge'))
                    <x-filament::button color="gray" wire:click="clearEditor">{{ __('ai_assistant.new') }}</x-filament::button>
                @endif
            </div>
            <div class="max-h-80 space-y-2 overflow-y-auto">
                @forelse ($this->documents() as $doc)
                    <div wire:key="document-{{ $doc['id'] }}" class="rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-medium">{{ $doc['title'] }}</span>
                                <span class="text-xs text-gray-500">({{ strtoupper($doc['locale']) }})</span>
                            </div>
                            @if (\App\Services\AI\AiAccess::allows('manage_ai_knowledge'))
                                <x-filament::button size="xs" color="gray" wire:click="edit('{{ $doc['id'] }}')">{{ __('ai_assistant.edit') }}</x-filament::button>
                            @endif
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($doc['versions'] as $version)
                                <div class="rounded bg-gray-100 px-2 py-1 text-xs text-gray-800 dark:bg-gray-800 dark:text-gray-200">
                                    v{{ $version['version_no'] }} · {{ $version['status'] }}
                                    @if ($doc['active_version_id'] === $version['id'])
                                        ✓
                                    @endif
                                    @if (in_array($version['status'], ['draft', 'failed'], true) && \App\Services\AI\AiAccess::allows('publish_ai_knowledge'))
                                        <button type="button" class="font-semibold underline" wire:click="approve('{{ $doc['id'] }}', '{{ $version['id'] }}')">
                                            {{ __('ai_assistant.publish') }}
                                        </button>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">{{ __('ai_assistant.empty') }}</p>
                @endforelse
            </div>
            @if (\App\Services\AI\AiAccess::allows('manage_ai_knowledge'))
                <form wire:submit="save" class="space-y-3">
                    <h3 class="font-semibold">{{ $editingDocument ? __('ai_assistant.new_version') : __('ai_assistant.new') }}</h3>
                    <label class="block text-sm">{{ __('ai_assistant.title') }}
                        <input type="text" wire:model="titleInput" maxlength="200" required class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    </label>
                    <label class="block text-sm">{{ __('ai_assistant.category') }}
                        <input type="text" wire:model="categoryInput" maxlength="100" class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    </label>
                    <label class="block text-sm">{{ __('ai_assistant.language') }}
                        <select wire:model="localeInput" class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="en">English</option><option value="pl">Polski</option><option value="pt">Português</option>
                        </select>
                    </label>
                    <label class="block text-sm">{{ __('ai_assistant.content') }}
                        <textarea wire:model="contentInput" rows="9" maxlength="30000" required class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                    </label>
                    <div class="text-xs text-gray-500">{{ __('ai_assistant.draft_notice') }}</div>
                    @error('contentInput') <div class="text-red-600">{{ $message }}</div> @enderror
                    <x-filament::button type="submit">{{ __('ai_assistant.save_draft') }}</x-filament::button>
                </form>
            @endif
        </section>

        <section class="space-y-5 rounded-xl border border-gray-200 p-5 dark:border-gray-700">
            <h2 class="text-lg font-semibold">{{ __('ai_assistant.test') }}</h2>
            @if (\App\Services\AI\AiAccess::allows('manage_ai_knowledge'))
                <div class="flex flex-wrap items-center gap-3">
                    <span class="text-sm">{{ __('ai_assistant.assistant') }}: {{ $assistantEnabled ? __('ai_assistant.enabled') : __('ai_assistant.disabled') }}</span>
                    <x-filament::button color="gray" wire:click="toggleAssistant">{{ __('ai_assistant.toggle') }}</x-filament::button>
                </div>
            @endif
            <p class="text-xs text-gray-500">{{ __('ai_assistant.preview_notice') }}</p>
            @if (\App\Services\AI\AiAccess::allows('test_ai_knowledge'))
                <form wire:submit="test" class="space-y-3">
                    <label class="block text-sm">{{ __('ai_assistant.question') }}
                        <textarea wire:model="questionInput" maxlength="2000" rows="3" required class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                    </label>
                    <label class="block text-sm">{{ __('ai_assistant.language') }}
                        <select wire:model="questionLocale" class="mt-1 w-full rounded-lg border-gray-300 text-gray-900 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                            <option value="en">English</option><option value="pl">Polski</option><option value="pt">Português</option>
                        </select>
                    </label>
                    <x-filament::button type="submit" wire:loading.attr="disabled">{{ __('ai_assistant.run_test') }}</x-filament::button>
                </form>
                @if ($testResult !== null)
                    <div class="space-y-3 rounded-lg bg-gray-50 p-4 dark:bg-gray-800">
                        <div class="text-xs font-bold uppercase">{{ $testResult['status'] }}</div>
                        <div class="whitespace-pre-wrap break-words text-sm">{{ $testResult['answer'] }}</div>
                        @foreach ($testResult['sources'] as $source)
                            <div class="border-t border-gray-300 pt-2 text-xs dark:border-gray-600">
                                {{ $source['title'] }} · v{{ $source['version'] }} · {{ $source['score'] }}
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </section>
    </div>
</x-filament-panels::page>
