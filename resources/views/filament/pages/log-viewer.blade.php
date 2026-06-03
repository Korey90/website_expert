<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Filters bar --}}
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">

                {{-- File selector --}}
                <div class="flex-1 min-w-48">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Log file
                    </label>
                    <select
                        wire:model.live="selectedFile"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        @forelse($this->logFiles() as $file)
                            <option value="{{ $file }}">{{ $file }}</option>
                        @empty
                            <option value="">— no log files —</option>
                        @endforelse
                    </select>
                </div>

                {{-- Level filter --}}
                <div class="min-w-36">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Level
                    </label>
                    <select
                        wire:model.live="levelFilter"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    >
                        @foreach($this->levels() as $level)
                            <option value="{{ $level }}">{{ $level }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Search --}}
                <div class="flex-1 min-w-56">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Search
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="Filter by message…"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </div>

            </div>
        </x-filament::section>

        {{-- Log table --}}
        @php
            $data = $this->paginatedEntries();
        @endphp

        <x-filament::section>
            <x-slot name="heading">
                Entries
                <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                    ({{ number_format($data['total']) }} total)
                </span>
            </x-slot>

            @if(count($data['items']) === 0)
                <p class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">No log entries found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                <th class="py-2 pr-4 font-semibold text-gray-600 dark:text-gray-400 whitespace-nowrap w-44">Date / Time</th>
                                <th class="py-2 pr-4 font-semibold text-gray-600 dark:text-gray-400 w-24">Level</th>
                                <th class="py-2 font-semibold text-gray-600 dark:text-gray-400">Message</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($data['items'] as $entry)
                                <tr class="align-top hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2 pr-4 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap font-mono">
                                        {{ $entry['datetime'] ?: '—' }}
                                    </td>
                                    <td class="py-2 pr-4">
                                        @php
                                            $badgeClass = match(strtoupper($entry['level'])) {
                                                'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY' =>
                                                    'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                'WARNING' =>
                                                    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                                'NOTICE', 'INFO' =>
                                                    'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                                'DEBUG' =>
                                                    'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
                                                default =>
                                                    'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                            };
                                        @endphp
                                        <span class="inline-block rounded px-1.5 py-0.5 text-xs font-semibold {{ $badgeClass }}">
                                            {{ $entry['level'] }}
                                        </span>
                                    </td>
                                    <td class="py-2">
                                        <p class="font-mono text-xs text-gray-800 dark:text-gray-200 break-all whitespace-pre-wrap leading-relaxed">{{ $entry['message'] }}</p>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Pagination --}}
                @if($data['totalPages'] > 1)
                    <div class="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                        <span>
                            Page {{ $data['page'] }} of {{ $data['totalPages'] }}
                            &nbsp;&middot;&nbsp;
                            {{ number_format($data['total']) }} entries
                        </span>
                        <div class="flex gap-2">
                            <button
                                wire:click="previousPage"
                                @if($data['page'] <= 1) disabled @endif
                                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                &larr; Prev
                            </button>
                            <button
                                wire:click="nextPage"
                                @if($data['page'] >= $data['totalPages']) disabled @endif
                                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
                            >
                                Next &rarr;
                            </button>
                        </div>
                    </div>
                @endif
            @endif
        </x-filament::section>

    </div>
</x-filament-panels::page>
