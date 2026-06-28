<x-filament-panels::page>
    @php
        $typeColors = [
            'discovery'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
            'qualification'  => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
            'proposal_input' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
            'sales_offer'    => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-300',
            'custom'         => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
        ];
        $typeLabels = [
            'discovery'      => 'Discovery',
            'qualification'  => 'Qualification',
            'proposal_input' => 'Proposal Input',
            'sales_offer'    => 'Sales Offer',
            'custom'         => 'Custom',
        ];
        $langLabels = ['en' => 'English', 'pl' => 'Polish', 'pt' => 'Portuguese'];
        $typeColor  = $typeColors[$record->type] ?? $typeColors['custom'];
        $typeLabel  = $typeLabels[$record->type] ?? ucfirst($record->type);
        $sections   = $record->sections ?? [];
        $totalQs    = collect($sections)->sum(fn ($s) => count($s['questions'] ?? []));
    @endphp

    {{-- ═══════════════════════════════════════════════════════
         PRINT STYLES  (only active during window.print())
    ═══════════════════════════════════════════════════════ --}}
    <style>
        @media print {
            /* hide Filament chrome */
            aside, nav, header.fi-topbar,
            .fi-sidebar, .fi-topbar, .fi-breadcrumbs,
            [data-fi-actions-dropdown], .fi-page-header-actions,
            .fi-btn { display: none !important; }

            body { background: #fff !important; }
            .print-document { box-shadow: none !important; border: none !important; }
            .no-print { display: none !important; }
            .print-break-inside { break-inside: avoid; }
        }
    </style>

    {{-- ══════════════════════════════════════════════════════
         DOCUMENT WRAPPER
    ══════════════════════════════════════════════════════ --}}
    <div class="print-document mx-auto max-w-4xl space-y-0 overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-900">

        {{-- ── Document Header ──────────────────────────────────── --}}
        <div class="border-b border-gray-100 bg-gradient-to-r from-gray-50 to-white px-8 py-6 dark:border-gray-800 dark:from-gray-900 dark:to-gray-900">
            <div class="flex flex-wrap items-start justify-between gap-4">

                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-xl bg-indigo-50 dark:bg-indigo-900/30">
                        <x-heroicon-o-document-text class="h-6 w-6 text-indigo-600 dark:text-indigo-400" />
                    </div>
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-widest text-gray-400 dark:text-gray-500">
                            Briefing Template
                            @if($record->isGlobal())
                                &middot; <span class="text-indigo-500">Global</span>
                            @endif
                        </p>
                        <h1 class="mt-0.5 text-2xl font-bold text-gray-900 dark:text-white">
                            {{ $record->title }}
                        </h1>
                    </div>
                </div>

                {{-- Status badge --}}
                <div class="flex items-center gap-2">
                    @if($record->is_active)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-300">
                            <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                            <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                            Inactive
                        </span>
                    @endif
                </div>
            </div>

            {{-- Meta row --}}
            <div class="mt-5 flex flex-wrap items-center gap-3">
                <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-xs font-semibold {{ $typeColor }}">
                    {{ $typeLabel }}
                </span>

                @if($record->service_slug)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-heroicon-m-wrench-screwdriver class="h-3 w-3" />
                        {{ $record->service_slug }}
                    </span>
                @endif

                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <x-heroicon-m-language class="h-3 w-3" />
                    {{ $langLabels[$record->language] ?? strtoupper($record->language) }}
                </span>

                <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    <x-heroicon-m-list-bullet class="h-3 w-3" />
                    {{ count($sections) }} section{{ count($sections) !== 1 ? 's' : '' }}
                    &middot;
                    {{ $totalQs }} question{{ $totalQs !== 1 ? 's' : '' }}
                </span>

                @if($record->business)
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-gray-100 px-3 py-1 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                        <x-heroicon-m-building-office class="h-3 w-3" />
                        {{ $record->business->name }}
                    </span>
                @endif
            </div>

            {{-- Description --}}
            @if($record->description)
                <p class="mt-4 text-sm leading-relaxed text-gray-600 dark:text-gray-400">
                    {{ $record->description }}
                </p>
            @endif
        </div>

        {{-- ── Sections & Questions ─────────────────────────────── --}}
        @if(count($sections) > 0)
            <div class="divide-y divide-gray-100 dark:divide-gray-800">
                @foreach($sections as $sectionIndex => $section)
                    <div class="print-break-inside px-8 py-6">
                        {{-- Section heading --}}
                        <div class="mb-5 flex items-center gap-3">
                            <div class="flex h-7 w-7 flex-shrink-0 items-center justify-center rounded-lg bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                {{ $sectionIndex + 1 }}
                            </div>
                            <div>
                                <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                                    {{ $section['title'] }}
                                </h2>
                                @if(!empty($section['key']))
                                    <p class="text-xs text-gray-400 dark:text-gray-500">key: <code class="font-mono">{{ $section['key'] }}</code></p>
                                @endif
                            </div>
                        </div>

                        {{-- Questions grid --}}
                        @php $questions = $section['questions'] ?? []; @endphp
                        @if(count($questions) > 0)
                            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                                @foreach($questions as $qIndex => $question)
                                    @php
                                        $qType     = $question['type'] ?? 'text';
                                        $isWide    = in_array($qType, ['textarea']);
                                        $typeIcon  = match($qType) {
                                            'textarea' => 'heroicon-m-bars-3-bottom-left',
                                            'select'   => 'heroicon-m-chevron-down',
                                            'boolean'  => 'heroicon-m-check-circle',
                                            'rating'   => 'heroicon-m-star',
                                            default    => 'heroicon-m-minus',
                                        };
                                        $typeLabel = match($qType) {
                                            'text'     => 'Text',
                                            'textarea' => 'Textarea',
                                            'select'   => 'Select',
                                            'boolean'  => 'Yes / No',
                                            'rating'   => 'Rating 1–5',
                                            default    => $qType,
                                        };
                                    @endphp
                                    <div @class([
                                        'rounded-xl border border-gray-100 bg-gray-50 p-4 dark:border-gray-800 dark:bg-gray-800/50',
                                        'sm:col-span-2' => $isWide,
                                    ])>
                                        <div class="mb-2 flex items-start justify-between gap-2">
                                            <div class="flex items-start gap-2">
                                                <span class="mt-0.5 text-xs font-semibold text-gray-400 dark:text-gray-500">
                                                    {{ $sectionIndex + 1 }}.{{ $qIndex + 1 }}
                                                </span>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                                        {{ $question['label'] }}
                                                        @if(!empty($question['required']))
                                                            <span class="ml-1 text-red-500">*</span>
                                                        @endif
                                                    </p>
                                                    @if(!empty($question['key']))
                                                        <p class="text-xs text-gray-400 dark:text-gray-500">
                                                            <code class="font-mono">{{ $question['key'] }}</code>
                                                        </p>
                                                    @endif
                                                </div>
                                            </div>

                                            <span class="inline-flex flex-shrink-0 items-center gap-1 rounded-md bg-white px-2 py-0.5 text-xs font-medium text-gray-500 shadow-sm ring-1 ring-gray-200 dark:bg-gray-700 dark:text-gray-400 dark:ring-gray-600">
                                                <x-dynamic-component :component="$typeIcon" class="h-3 w-3" />
                                                {{ $typeLabel }}
                                            </span>
                                        </div>

                                        {{-- Field preview --}}
                                        <div class="mt-2">
                                            @if($qType === 'textarea')
                                                <div class="min-h-[4rem] rounded-lg border border-dashed border-gray-200 bg-white p-2 dark:border-gray-700 dark:bg-gray-900">
                                                    <span class="text-xs italic text-gray-300 dark:text-gray-600">
                                                        {{ $question['placeholder'] ?? 'Multi-line text…' }}
                                                    </span>
                                                </div>

                                            @elseif($qType === 'select')
                                                <div class="flex items-center gap-1 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                                                    <span class="flex-1 text-xs italic text-gray-300 dark:text-gray-600">Select an option</span>
                                                    <x-heroicon-m-chevron-down class="h-3 w-3 text-gray-300" />
                                                </div>
                                                @if(!empty($question['options']))
                                                    <div class="mt-1 flex flex-wrap gap-1">
                                                        @foreach($question['options'] as $opt)
                                                            <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-700 dark:text-gray-400">
                                                                {{ $opt }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif

                                            @elseif($qType === 'boolean')
                                                <div class="flex gap-3">
                                                    <span class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900">
                                                        <x-heroicon-m-check class="h-3 w-3" /> Yes
                                                    </span>
                                                    <span class="inline-flex items-center gap-1 rounded-lg border border-dashed border-gray-200 bg-white px-3 py-1.5 text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900">
                                                        <x-heroicon-m-x-mark class="h-3 w-3" /> No
                                                    </span>
                                                </div>

                                            @elseif($qType === 'rating')
                                                <div class="flex gap-1.5">
                                                    @foreach([1,2,3,4,5] as $n)
                                                        <span class="flex h-7 w-7 items-center justify-center rounded-lg border border-dashed border-gray-200 bg-white text-xs text-gray-400 dark:border-gray-700 dark:bg-gray-900">
                                                            {{ $n }}
                                                        </span>
                                                    @endforeach
                                                </div>

                                            @else
                                                <div class="rounded-lg border border-dashed border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900">
                                                    <span class="text-xs italic text-gray-300 dark:text-gray-600">
                                                        {{ $question['placeholder'] ?? 'Short text…' }}
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-sm italic text-gray-400 dark:text-gray-500">No questions in this section.</p>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="flex flex-col items-center justify-center py-16 text-center">
                <x-heroicon-o-document class="h-12 w-12 text-gray-200 dark:text-gray-700" />
                <p class="mt-3 text-sm text-gray-400 dark:text-gray-500">No sections defined yet.</p>
                <p class="mt-1 text-xs text-gray-300 dark:text-gray-600">Edit the template to add sections and questions.</p>
            </div>
        @endif

        {{-- ── Document Footer ──────────────────────────────────── --}}
        <div class="border-t border-gray-100 bg-gray-50 px-8 py-4 dark:border-gray-800 dark:bg-gray-900/50">
            <div class="flex flex-wrap items-center justify-between gap-2 text-xs text-gray-400 dark:text-gray-500">
                <span>
                    Created: {{ $record->created_at?->format('d M Y') }}
                    @if($record->updated_at && $record->updated_at->ne($record->created_at))
                        &middot; Updated: {{ $record->updated_at->format('d M Y') }}
                    @endif
                </span>
                <span class="font-mono">ID #{{ $record->id }}</span>
            </div>
        </div>
    </div>
</x-filament-panels::page>
