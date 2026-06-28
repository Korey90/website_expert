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

        {{-- Sentinel: sticky bar appears when this exits viewport --}}
        <div id="briefing-scroll-sentinel" class="h-px"></div>

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
                                $qKey        = $question['key'];
                                $wireKey     = "answers.{$sectionKey}.{$qKey}";
                                $label       = $question['label'];
                                $required    = $question['required'] ?? false;
                                $qType       = $question['type'] ?? 'text';
                                $placeholder = $question['placeholder'] ?? '';
                                $isPreFilled = in_array("{$sectionKey}.{$qKey}", $this->calculatorPrefilled, true);
                            @endphp
                            <div @class(['sm:col-span-2' => in_array($qType, ['textarea'])])>
                                <div class="mb-1 flex items-center gap-2">
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                        {{ $label }}
                                        @if($required) <span class="text-red-500">*</span> @endif
                                    </label>
                                    @if($isPreFilled)
                                        <span class="inline-flex items-center gap-1 rounded-md bg-amber-50 px-1.5 py-0.5 text-[10px] font-semibold text-amber-600 ring-1 ring-amber-200 dark:bg-amber-900/20 dark:text-amber-400 dark:ring-amber-700/50">
                                            <x-heroicon-m-calculator class="h-2.5 w-2.5" />
                                            calculator
                                        </span>
                                    @endif
                                </div>

                                @if ($qType === 'textarea')
                                    <textarea
                                        wire:model.live.debounce.1500ms="{{ $wireKey }}"
                                        rows="3"
                                        placeholder="{{ $placeholder }}"
                                        @class([
                                            'block w-full resize-y rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:outline-none dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500',
                                            'border-amber-300 focus:border-amber-400 dark:border-amber-700' => $isPreFilled,
                                            'border-gray-300 focus:border-primary-500 dark:border-gray-600' => !$isPreFilled,
                                        ])
                                    ></textarea>

                                @elseif ($qType === 'select')
                                    <select
                                        wire:model.live.debounce.1500ms="{{ $wireKey }}"
                                        @class([
                                            'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:outline-none dark:bg-gray-800 dark:text-white',
                                            'border-amber-300 focus:border-amber-400 dark:border-amber-700' => $isPreFilled,
                                            'border-gray-300 focus:border-primary-500 dark:border-gray-600' => !$isPreFilled,
                                        ])
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
                                        @class([
                                            'block w-full rounded-lg border bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:outline-none dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500',
                                            'border-amber-300 focus:border-amber-400 dark:border-amber-700' => $isPreFilled,
                                            'border-gray-300 focus:border-primary-500 dark:border-gray-600' => !$isPreFilled,
                                        ])
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
                    class="block w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm placeholder:text-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder:text-gray-500"
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

    {{-- ── Sticky action bar ──────────────────────────────────────────────── --}}
    <div
        x-data="{ visible: false }"
        x-init="
            const sentinel = document.getElementById('briefing-scroll-sentinel');
            if (sentinel) {
                const obs = new IntersectionObserver(
                    ([entry]) => { visible = !entry.isIntersecting; },
                    { threshold: 0 }
                );
                obs.observe(sentinel);
            }
        "
        x-show="visible"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 translate-y-4"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 translate-y-4"
        class="fixed bottom-6 left-1/2 z-20 -translate-x-1/2"
        style="display: none;"
    >
        <div class="flex items-center gap-2 rounded-2xl border border-gray-200 bg-white px-4 py-2.5 shadow-xl dark:border-gray-700 dark:bg-gray-900">

            @if ($record->isEditable())
                {{-- Save progress --}}
                <button
                    type="button"
                    x-on:click="$wire.saveProgress()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
                >
                    <x-heroicon-o-cloud-arrow-up class="h-4 w-4" />
                    Save
                </button>

                <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>

                {{-- Share with client --}}
                <button
                    type="button"
                    x-on:click="if (confirm('Generate a client share link for this briefing?')) $wire.shareWithClient()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-info-600 transition hover:bg-info-50 dark:text-info-400 dark:hover:bg-info-950"
                >
                    <x-heroicon-o-share class="h-4 w-4" />
                    Share
                </button>

                {{-- Complete --}}
                <button
                    type="button"
                    x-on:click="if (confirm('Mark this briefing as completed? Make sure all required fields are filled.')) $wire.completeBriefing()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-success-600 transition hover:bg-success-50 dark:text-success-400 dark:hover:bg-success-950"
                >
                    <x-heroicon-o-check-circle class="h-4 w-4" />
                    Complete
                </button>

                {{-- Cancel --}}
                <button
                    type="button"
                    x-on:click="if (confirm('Cancel this briefing? This action cannot be undone.')) $wire.cancelBriefing()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-danger-600 transition hover:bg-danger-50 dark:text-danger-400 dark:hover:bg-danger-950"
                >
                    <x-heroicon-o-x-circle class="h-4 w-4" />
                    Cancel
                </button>

                <div class="h-5 w-px bg-gray-200 dark:bg-gray-700"></div>
            @endif

            {{-- Export PDF --}}
            <button
                type="button"
                x-on:click="$wire.mountAction('export_pdf')"
                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-300 dark:hover:bg-gray-800"
            >
                <x-heroicon-o-arrow-down-tray class="h-4 w-4" />
                PDF
            </button>
        </div>
    </div>
</x-filament-panels::page>
