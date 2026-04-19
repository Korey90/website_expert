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
                                'draft'       => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
                                'in_progress' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                                'completed'   => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                                'cancelled'   => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                            ];
                            $statusColor = $statusColors[$record->status] ?? 'bg-gray-100 text-gray-700';
                        @endphp
                        <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColor }}">
                            {{ ucfirst(str_replace('_', ' ', $record->status)) }}
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
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Conducted by</p>
                    <p class="mt-1 text-sm text-gray-700 dark:text-gray-300">{{ $record->conductedBy->name ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-500 dark:text-gray-400">Progress</p>
                    <p class="mt-1 text-sm font-bold text-gray-900 dark:text-gray-100">{{ $this->getProgress() }}%</p>
                    <div class="mt-1 h-1.5 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                        <div class="h-1.5 rounded-full bg-primary-600 transition-all" style="width: {{ $this->getProgress() }}%"></div>
                    </div>
                </div>
            </div>
            @if($record->autosave_at)
                <p class="mt-2 text-xs text-gray-400">
                    Last autosave: {{ $record->autosave_at->format('d/m/Y H:i:s') }}
                </p>
            @endif
        </div>

        {{-- Questions sections --}}
        @if ($record->isEditable())
            @foreach ($this->getSections() as $section)
                @php $sectionKey = $section['key']; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">
                        {{ $section['title'] }}
                    </h3>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($section['questions'] ?? [] as $question)
                            @php
                                $qKey      = $question['key'];
                                $wireKey   = "answers.{$sectionKey}.{$qKey}";
                                $label     = $question['label'];
                                $required  = $question['required'] ?? false;
                                $qType     = $question['type'] ?? 'text';
                                $placeholder = $question['placeholder'] ?? '';
                            @endphp
                            <div @class(['sm:col-span-2' => in_array($qType, ['textarea'])])>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                    {{ $label }}
                                    @if($required) <span class="text-red-500">*</span> @endif
                                </label>

                                @if ($qType === 'textarea')
                                    <textarea
                                        wire:model.live.debounce.1500ms="{{ $wireKey }}"
                                        rows="3"
                                        placeholder="{{ $placeholder }}"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    ></textarea>

                                @elseif ($qType === 'select')
                                    <select
                                        wire:model.live.debounce.1500ms="{{ $wireKey }}"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                        <option value="">— Select —</option>
                                        @foreach ($question['options'] ?? [] as $opt)
                                            <option value="{{ $opt }}">{{ $opt }}</option>
                                        @endforeach
                                    </select>

                                @elseif ($qType === 'boolean')
                                    <div class="mt-2 flex items-center gap-4">
                                        <label class="flex items-center gap-1 text-sm">
                                            <input type="radio" wire:model.live.debounce.1500ms="{{ $wireKey }}" value="yes" class="text-primary-600">
                                            Yes
                                        </label>
                                        <label class="flex items-center gap-1 text-sm">
                                            <input type="radio" wire:model.live.debounce.1500ms="{{ $wireKey }}" value="no" class="text-primary-600">
                                            No
                                        </label>
                                    </div>

                                @elseif ($qType === 'rating')
                                    <div class="mt-2 flex items-center gap-2">
                                        @foreach ([1, 2, 3, 4, 5] as $n)
                                            <label class="flex flex-col items-center gap-0.5 text-xs cursor-pointer">
                                                <input type="radio" wire:model.live.debounce.1500ms="{{ $wireKey }}" value="{{ $n }}" class="text-primary-600">
                                                {{ $n }}
                                            </label>
                                        @endforeach
                                    </div>

                                @else
                                    <input
                                        type="text"
                                        wire:model.live.debounce.1500ms="{{ $wireKey }}"
                                        placeholder="{{ $placeholder }}"
                                        class="mt-1 block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                                    >
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach

            {{-- Notes --}}
            <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="mb-3 text-base font-semibold text-gray-900 dark:text-gray-100">Internal notes</h3>
                <textarea
                    wire:model.live.debounce.1500ms="notes"
                    rows="4"
                    placeholder="Add internal notes visible only to your team..."
                    class="block w-full rounded-lg border-gray-300 shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100 sm:text-sm"
                ></textarea>
            </div>

        @else
            {{-- Read-only view for completed/cancelled briefings --}}
            @foreach ($this->getSections() as $section)
                @php $sectionKey = $section['key']; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-4 text-base font-semibold text-gray-900 dark:text-gray-100">{{ $section['title'] }}</h3>
                    <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach ($section['questions'] ?? [] as $question)
                            <div>
                                <dt class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $question['label'] }}</dt>
                                <dd class="mt-0.5 text-sm text-gray-900 dark:text-gray-100">
                                    {{ $this->getAnswer($sectionKey, $question['key']) ?? '—' }}
                                </dd>
                            </div>
                        @endforeach
                    </dl>
                </div>
            @endforeach

            @if($record->notes)
                <div class="rounded-xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <h3 class="mb-2 text-base font-semibold text-gray-900 dark:text-gray-100">Internal notes</h3>
                    <p class="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{{ $record->notes }}</p>
                </div>
            @endif
        @endif
    </div>
</x-filament-panels::page>
