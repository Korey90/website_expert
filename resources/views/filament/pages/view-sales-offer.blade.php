<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Metadata bar --}}
        <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Status</p>
                    <p class="mt-1">
                        @php
                            $statusColors = [
                                'draft'     => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'sent'      => 'bg-sky-100 text-sky-700 dark:bg-sky-900/30 dark:text-sky-300',
                                'viewed'    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
                                'converted' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                            ];
                            $statusColor = $statusColors[$record->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst($record->status) }}
                        </span>
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Lead</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ $record->lead->title ?? '—' }}
                    </p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Created by</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $record->createdBy->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Language</p>
                    <p class="mt-1 text-sm font-medium text-gray-900 dark:text-gray-100">
                        {{ strtoupper($record->language) }}
                    </p>
                </div>
            </div>
            @if($record->sent_at)
                <div class="mt-3 flex flex-wrap gap-4 text-xs text-gray-500 dark:text-gray-400">
                    <span>Sent: {{ $record->sent_at->format('d/m/Y H:i') }}</span>
                    @if($record->viewed_at)
                        <span>Viewed: {{ $record->viewed_at->format('d/m/Y H:i') }}</span>
                    @endif
                    @if($record->client_token)
                        <span>
                            Public link:
                            <a href="{{ url('/offers/' . $record->client_token) }}"
                               target="_blank"
                               class="text-sky-600 hover:underline dark:text-sky-400">
                                /offers/{{ substr($record->client_token, 0, 12) }}…
                            </a>
                        </span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Body editor (draft) or read-only preview --}}
        <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
            <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">Offer Content</h3>

            @if ($record->isEditable())
                <div>
                    <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                        Body (Markdown) — autosaved
                    </label>
                    <textarea
                        wire:model.live.debounce.1500ms="body"
                        rows="24"
                        class="block w-full rounded-lg border-gray-300 font-mono text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                    ></textarea>
                </div>

                <div class="mt-4">
                    <label class="block text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400 mb-2">
                        Internal notes
                    </label>
                    <textarea
                        wire:model.live.debounce.1500ms="notes"
                        rows="3"
                        class="block w-full rounded-lg border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                        placeholder="Internal notes (not visible to client)..."
                    ></textarea>
                </div>
            @else
                {{-- Read-only markdown preview --}}
                <div class="prose prose-sm dark:prose-invert max-w-none">
                    {!! \Illuminate\Support\Str::markdown($record->body ?? '') !!}
                </div>

                @if($record->notes)
                    <div class="mt-6 rounded-lg bg-yellow-50 p-4 dark:bg-yellow-900/20">
                        <p class="text-xs font-medium uppercase tracking-wide text-yellow-700 dark:text-yellow-300 mb-1">Internal notes</p>
                        <p class="text-sm text-yellow-800 dark:text-yellow-200">{{ $record->notes }}</p>
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
