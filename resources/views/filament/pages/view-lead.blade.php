<x-filament-panels::page>
    @php
        $stageIds   = $allStages->pluck('id')->toArray();
        $rawIdx     = array_search($record->pipeline_stage_id, $stageIds);
        $currentIdx = $rawIdx === false ? 0 : $rawIdx;
        $hasPrev    = $currentIdx > 0;
        $hasNext    = $currentIdx < count($stageIds) - 1;
        $prevStage  = $hasPrev ? $allStages[$currentIdx - 1] : null;
        $nextStage  = $hasNext ? $allStages[$currentIdx + 1] : null;

        $stageIsWon  = $record->stage?->is_won  ?? false;
        $stageIsLost = $record->stage?->is_lost ?? false;

        $currencySymbol = match ($record->currency ?? 'GBP') {
            'GBP'   => '£',
            'EUR'   => '€',
            'PLN'   => 'zł',
            default => $record->currency ?? '',
        };

        $sourceLabel = match ($record->source ?? '') {
            'calculator'    => 'Cost Calculator',
            'contact_form'  => 'Contact Form',
            'website'       => 'Website',
            'referral'      => 'Referral',
            'cold_outreach' => 'Cold Outreach',
            'social_media'  => 'Social Media',
            'google_ads'    => 'Google Ads',
            'other'         => 'Other',
            default         => ucwords(str_replace('_', ' ', $record->source ?? 'Unknown')),
        };

        $stageBadgeClass = $stageIsWon
            ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
            : ($stageIsLost
                ? 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'
                : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400');
    @endphp

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ════════════════════════════════════════════════════════
             LEFT COLUMN  ·  Lead Details · Notes · Timeline
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- ── Lead Details ──────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <x-heroicon-m-identification class="h-4 w-4" />
                        Lead Details
                    </h2>
                    @if($record->won_at)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-3 py-1 text-xs font-semibold text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <x-heroicon-m-trophy class="h-3.5 w-3.5" />
                            WON · {{ $record->won_at->format('d M Y') }}
                        </span>
                    @elseif($record->lost_at)
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/30 dark:text-red-400">
                            <x-heroicon-m-x-circle class="h-3.5 w-3.5" />
                            LOST · {{ $record->lost_at->format('d M Y') }}
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-x-8 gap-y-5 px-6 py-5 sm:grid-cols-3">
                    {{-- Value --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Deal Value</dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white">
                            @if($record->value)
                                {{ $currencySymbol }}{{ number_format((float) $record->value, 0) }}
                            @else
                                <span class="text-base font-normal text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Stage --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Current Stage</dt>
                        <dd>
                            @if($record->stage)
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-sm font-medium {{ $stageBadgeClass }}">
                                    {{ $record->stage->name }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Source --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Source</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $sourceLabel }}
                            </span>
                        </dd>
                    </div>

                    {{-- Assigned To --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Assigned To</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            @if($record->assignedTo)
                                <span class="flex items-center gap-1.5">
                                    <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                        {{ mb_strtoupper(mb_substr($record->assignedTo->name, 0, 1)) }}
                                    </span>
                                    {{ $record->assignedTo->name }}
                                </span>
                            @else
                                <span class="text-gray-400">Unassigned</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Expected Close --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Expected Close</dt>
                        <dd class="text-sm">
                            @if($record->expected_close_date)
                                @php $overdue = $record->expected_close_date->isPast() && ! $stageIsWon && ! $stageIsLost; @endphp
                                <span class="{{ $overdue ? 'font-semibold text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $record->expected_close_date->format('d M Y') }}
                                </span>
                                @if($overdue)
                                    <span class="ml-1 rounded bg-red-100 px-1.5 py-0.5 text-xs font-medium text-red-600 dark:bg-red-900/30 dark:text-red-400">overdue</span>
                                @endif
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Created --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->created_at->format('d M Y') }}
                            <span class="ml-1 text-xs text-gray-400">{{ $record->created_at->diffForHumans() }}</span>
                        </dd>
                    </div>

                    {{-- Lost Reason --}}
                    @if($record->lost_reason)
                        <div class="col-span-full">
                            <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Lost Reason</dt>
                            <dd class="rounded-lg bg-red-50 px-3 py-2 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400">{{ $record->lost_reason }}</dd>
                        </div>
                    @endif

                    {{-- Legacy notes field --}}
                    @if($record->notes)
                        <div class="col-span-full border-t border-gray-100 pt-4 dark:border-gray-800">
                            <dt class="mb-2 text-xs text-gray-400 dark:text-gray-500">Legacy Notes</dt>
                            <dd class="rounded-lg bg-gray-50 p-3 text-sm leading-relaxed text-gray-700 dark:bg-gray-800/50 dark:text-gray-300">{{ $record->notes }}</dd>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Notes ─────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</h2>
                    @if($leadNotes->count())
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">{{ $leadNotes->count() }}</span>
                    @endif
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @forelse($leadNotes as $note)
                        <div class="px-6 py-4 {{ $note->is_pinned ? 'bg-yellow-50/60 dark:bg-yellow-900/5' : '' }}">

                            @if($editNoteId === $note->id)
                                {{-- Edit mode --}}
                                <textarea wire:model="editNoteText"
                                          rows="4"
                                          class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                                @error('editNoteText')
                                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                                @enderror
                                <div class="mt-2 flex gap-2">
                                    <button wire:click="saveEditNote"
                                            class="flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-primary-700">
                                        <x-heroicon-m-check class="h-3.5 w-3.5" /> Save
                                    </button>
                                    <button wire:click="cancelEditNote"
                                            class="rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                        Cancel
                                    </button>
                                </div>

                            @else
                                {{-- View mode --}}
                                @if($note->is_pinned)
                                    <div class="mb-2 flex items-center gap-1">
                                        <x-heroicon-m-star class="h-3.5 w-3.5 text-yellow-500" />
                                        <span class="text-xs font-medium text-yellow-600 dark:text-yellow-400">Pinned</span>
                                    </div>
                                @endif

                                <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $note->content }}</p>

                                <div class="mt-3 flex items-center justify-between">
                                    <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                        @if($note->user)
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-user class="h-3 w-3" />
                                                {{ $note->user->name }}
                                            </span>
                                            <span>·</span>
                                        @endif
                                        <span>{{ $note->created_at->format('d M Y, H:i') }}</span>
                                        @if($note->updated_at->gt($note->created_at->addSeconds(5)))
                                            <span class="italic text-gray-300 dark:text-gray-600">(edited)</span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-0.5">
                                        <button wire:click="togglePinNote({{ $note->id }})"
                                                title="{{ $note->is_pinned ? 'Unpin' : 'Pin note' }}"
                                                class="rounded-md p-1 transition {{ $note->is_pinned ? 'text-yellow-500 hover:text-yellow-600' : 'text-gray-300 hover:text-yellow-400 dark:text-gray-600 dark:hover:text-yellow-400' }}">
                                            <x-heroicon-m-star class="h-4 w-4" />
                                        </button>
                                        <button wire:click="startEditNote({{ $note->id }})"
                                                title="Edit note"
                                                class="rounded-md p-1 text-gray-400 transition hover:text-blue-500 dark:text-gray-600 dark:hover:text-blue-400">
                                            <x-heroicon-m-pencil class="h-4 w-4" />
                                        </button>
                                        <button wire:click="deleteNote({{ $note->id }})"
                                                wire:confirm="Delete this note? This cannot be undone."
                                                title="Delete note"
                                                class="rounded-md p-1 text-gray-400 transition hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400">
                                            <x-heroicon-m-trash class="h-4 w-4" />
                                        </button>
                                    </div>
                                </div>
                            @endif

                        </div>
                    @empty
                        <div class="py-10 text-center">
                            <x-heroicon-o-document-text class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm text-gray-400">No notes yet. Add the first one below.</p>
                        </div>
                    @endforelse
                </div>

                {{-- Add note form --}}
                <div class="border-t border-gray-100 bg-gray-50/50 px-6 py-4 dark:border-gray-800 dark:bg-gray-800/30">
                    <textarea wire:model="newNoteText"
                              rows="3"
                              placeholder="Write a note..."
                              class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white dark:placeholder-gray-500"></textarea>
                    @error('newNoteText')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
                    <div class="mt-2 flex justify-end">
                        <button wire:click="addNote"
                                class="flex items-center gap-2 rounded-xl bg-primary-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary-700">
                            <x-heroicon-m-plus class="h-4 w-4" />
                            Add Note
                        </button>
                    </div>
                </div>
            </div>

            {{-- ── Activity Timeline ──────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-clock class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Activity History</h2>
                    @if($activities->count())
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">{{ $activities->count() }}</span>
                    @endif
                </div>

                <div class="px-6 py-5">
                    @if($activities->isEmpty())
                        <div class="py-10 text-center">
                            <x-heroicon-o-clock class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                            <p class="text-sm text-gray-400">No activity recorded yet.</p>
                        </div>
                    @else
                        <ol class="relative ml-3 space-y-0 border-l border-gray-200 dark:border-gray-700">
                            @foreach($activities as $activity)
                                <li class="mb-5 ml-5">
                                    <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $activity->bg }}">
                                        <x-dynamic-component :component="$activity->icon" class="h-3 w-3 {{ $activity->color }}" />
                                    </span>
                                    <div class="rounded-lg border border-gray-100 bg-gray-50 px-4 py-2.5 dark:border-gray-700/60 dark:bg-gray-800/50">
                                        <p class="text-sm font-medium text-gray-800 dark:text-gray-100">{{ $activity->description }}</p>

                                        @if($activity->metadata && count($activity->metadata) > 0)
                                            <div class="mt-1.5 flex flex-wrap gap-1.5">
                                                @foreach($activity->metadata as $key => $val)
                                                    @if($val !== null && $val !== '')
                                                        <span class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                            <span class="font-medium text-gray-400 dark:text-gray-500">{{ str_replace('_', ' ', $key) }}:</span>
                                                            {{ is_array($val) ? implode(', ', $val) : $val }}
                                                        </span>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="mt-1.5 flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                                            @if($activity->user)
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-m-user class="h-3 w-3" />
                                                    {{ $activity->user->name }}
                                                </span>
                                            @else
                                                <span class="flex items-center gap-1">
                                                    <x-heroicon-m-globe-alt class="h-3 w-3" />
                                                    System
                                                </span>
                                            @endif
                                            <span>{{ $activity->created_at->format('d M Y, H:i') }}</span>
                                            <span class="ml-auto">{{ $activity->created_at->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    @endif
                </div>
            </div>

        </div>{{-- end left column --}}


        {{-- ════════════════════════════════════════════════════════
             RIGHT COLUMN  ·  Quick Actions · Client · Calculator
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- ── Quick Actions ─────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                    <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                        <x-heroicon-m-bolt class="h-4 w-4 text-amber-400" />
                        Quick Actions
                    </h2>
                </div>
                <div class="p-4 space-y-3">

                    {{-- Stage navigation --}}
                    <div class="rounded-xl bg-gray-50 p-3 dark:bg-gray-800/50">
                        <p class="mb-0.5 text-xs text-gray-400 dark:text-gray-500">Current Stage</p>
                        <p class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">{{ $record->stage?->name ?? '—' }}</p>

                        {{-- Progress bar --}}
                        <div class="mb-2 flex items-center gap-1">
                            @foreach($allStages as $i => $stage)
                                <div class="h-1.5 flex-1 rounded-full
                                    {{ $i < $currentIdx ? 'bg-indigo-300 dark:bg-indigo-700' :
                                       ($i === $currentIdx
                                           ? ($stageIsWon ? 'bg-green-500' : ($stageIsLost ? 'bg-red-400' : 'bg-indigo-500'))
                                           : 'bg-gray-200 dark:bg-gray-700')
                                    }}"
                                     title="{{ $stage->name }}">
                                </div>
                            @endforeach
                        </div>
                        <p class="mb-3 text-center text-xs text-gray-400">{{ $currentIdx + 1 }} / {{ count($stageIds) }} stages</p>

                        {{-- Prev / Next buttons --}}
                        <div class="flex gap-2">
                            <button wire:click="moveStage('back')"
                                    @disabled(!$hasPrev)
                                    title="{{ $prevStage ? 'Back to: ' . $prevStage->name : 'No previous stage' }}"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-2 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                <x-heroicon-m-arrow-left class="h-3.5 w-3.5 shrink-0" />
                                <span class="truncate">{{ $prevStage?->name ?? 'Back' }}</span>
                            </button>
                            <button wire:click="moveStage('forward')"
                                    @disabled(!$hasNext)
                                    title="{{ $nextStage ? 'Move to: ' . $nextStage->name : 'No next stage' }}"
                                    class="flex flex-1 items-center justify-center gap-1.5 rounded-lg border border-gray-300 px-2 py-2 text-xs font-medium text-gray-700 transition hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                                <span class="truncate">{{ $nextStage?->name ?? 'Forward' }}</span>
                                <x-heroicon-m-arrow-right class="h-3.5 w-3.5 shrink-0" />
                            </button>
                        </div>
                    </div>

                    {{-- ── Stage Checklist ──────────────────────────────────── --}}
                    @if(count($stageChecklist) > 0)
                        @php
                            $doneCount  = collect($stageChecklist)->keys()->filter(
                                fn($i) => ($autoSatisfied[$i] ?? false) || isset($completedItems[$i])
                            )->count();
                            $totalCount = count($stageChecklist);
                            $pct        = $totalCount > 0 ? round($doneCount / $totalCount * 100) : 0;
                        @endphp
                        <div class="rounded-xl bg-amber-50/60 p-3 dark:bg-amber-900/10">
                            <div class="mb-2.5 flex items-center justify-between">
                                <p class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide text-amber-700 dark:text-amber-400">
                                    <x-heroicon-m-clipboard-document-check class="h-3.5 w-3.5" />
                                    Stage Checklist
                                </p>
                                <span class="text-xs text-gray-400">{{ $doneCount }}/{{ $totalCount }}</span>
                            </div>

                            <div class="space-y-0.5">
                                @foreach($stageChecklist as $i => $item)
                                    @php
                                        $isAuto        = $autoSatisfied[$i] ?? false;
                                        $isManual      = !$isAuto && isset($completedItems[$i]);
                                        $isDone        = $isAuto || $isManual;
                                        $itemCondition = $item['condition'] ?? null;
                                        $hasForm       = in_array($itemCondition, ['has_value', 'has_assignee', 'has_expected_close', 'has_notes', 'has_phone', 'has_email']);
                                        $hasEmailIcon  = $itemCondition === 'email_sent';
                                    @endphp

                                    <div @class([
                                            'flex w-full items-center rounded-lg text-sm transition group',
                                            'hover:bg-amber-100/70 dark:hover:bg-amber-900/20' => !$isAuto,
                                        ])>

                                        {{-- Toggle area (checkbox + label) --}}
                                        <button wire:click="{{ $isAuto ? '' : 'toggleChecklistItem(' . $i . ')' }}"
                                                @class([
                                                    'flex flex-1 items-start gap-2.5 min-w-0 px-2 py-1.5 text-left',
                                                    'cursor-default' => $isAuto,
                                                ])>

                                            {{-- Checkbox icon --}}
                                            @if($isAuto)
                                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 border-teal-500 bg-teal-500 text-white transition">
                                                    <x-heroicon-m-bolt class="h-2.5 w-2.5" />
                                                </span>
                                            @elseif($isManual)
                                                <span class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded border-2 border-green-500 bg-green-500 text-white transition">
                                                    <x-heroicon-m-check class="h-2.5 w-2.5" />
                                                </span>
                                            @else
                                                <span class="mt-0.5 h-4 w-4 shrink-0 rounded border-2 border-gray-300 bg-white transition dark:border-gray-500 dark:bg-gray-800"></span>
                                            @endif

                                            {{-- Label --}}
                                            <span @class([
                                                    'flex-1 leading-snug truncate',
                                                    'text-teal-500 line-through dark:text-teal-600' => $isAuto,
                                                    'text-gray-400 line-through dark:text-gray-500' => $isManual,
                                                    'text-gray-700 dark:text-gray-200'              => !$isDone,
                                                ])>
                                                {{ $item['label'] ?? $item }}
                                            </span>
                                        </button>

                                        {{-- Right: badge + action icon --}}
                                        <div class="flex shrink-0 items-center gap-1 pr-1.5">
                                            @if($isAuto)
                                                <span class="rounded-md bg-teal-100 px-1.5 py-0.5 text-xs font-medium text-teal-600 dark:bg-teal-900/30 dark:text-teal-400">
                                                    auto
                                                </span>
                                            @elseif($isManual && isset($completedItems[$i]))
                                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                                    {{ $completedItems[$i]->completed_at?->format('d M') }}
                                                </span>
                                            @endif

                                            @if($hasForm)
                                                <button wire:click="openChecklistItemModal({{ $i }})"
                                                        title="Enter data"
                                                        @class([
                                                            'rounded p-0.5 transition',
                                                            'text-gray-300 hover:text-amber-500 dark:text-gray-600 dark:hover:text-amber-400' => $isDone,
                                                            'text-gray-400 hover:text-amber-600 dark:text-gray-500 dark:hover:text-amber-400 opacity-0 group-hover:opacity-100' => !$isDone,
                                                        ])>
                                                    <x-heroicon-m-pencil-square class="h-3.5 w-3.5" />
                                                </button>
                                            @elseif($hasEmailIcon)
                                                <button wire:click="openEmailModal"
                                                        title="Send email"
                                                        @class([
                                                            'rounded p-0.5 transition',
                                                            'text-gray-300 hover:text-primary-500 dark:text-gray-600 dark:hover:text-primary-400' => $isDone,
                                                            'text-gray-400 hover:text-primary-600 dark:text-gray-500 dark:hover:text-primary-400 opacity-0 group-hover:opacity-100' => !$isDone,
                                                        ])>
                                                    <x-heroicon-m-envelope class="h-3.5 w-3.5" />
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            {{-- Progress bar --}}
                            <div class="mt-3 border-t border-amber-200/60 pt-2.5 dark:border-amber-800/40">
                                <div class="mb-1 h-1.5 overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                    <div class="h-1.5 rounded-full transition-all duration-300
                                        {{ $pct === 100 ? 'bg-green-500' : 'bg-amber-400' }}"
                                         style="width: {{ $pct }}%"></div>
                                </div>
                                @if($pct === 100)
                                    <p class="flex items-center justify-center gap-1 text-xs font-medium text-green-600 dark:text-green-400">
                                        <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
                                        All done — ready to move forward!
                                    </p>
                                @else
                                    <p class="text-center text-xs text-gray-400">{{ $pct }}% complete</p>
                                @endif
                            </div>

                            {{-- Legend --}}
                            <div class="mt-2 flex items-center justify-center gap-4 text-xs text-gray-400">
                                <span class="flex items-center gap-1">
                                    <span class="flex h-3 w-3 items-center justify-center rounded border border-teal-500 bg-teal-500 text-white">
                                        <x-heroicon-m-bolt class="h-2 w-2" />
                                    </span>
                                    Auto-detected
                                </span>
                                <span class="flex items-center gap-1">
                                    <span class="flex h-3 w-3 items-center justify-center rounded border border-green-500 bg-green-500 text-white">
                                        <x-heroicon-m-check class="h-2 w-2" />
                                    </span>
                                    Manually done
                                </span>
                            </div>
                        </div>
                    @endif

                    <div class="space-y-2 border-t border-gray-100 pt-2 dark:border-gray-800">

                        {{-- Mark Won --}}
                        @if(! $stageIsWon)
                            <button wire:click="markWon"
                                    wire:confirm="Mark this lead as Won?"
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700 transition hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30">
                                <x-heroicon-m-trophy class="h-4 w-4 shrink-0" />
                                Mark as Won
                            </button>
                        @endif

                        {{-- Mark Lost --}}
                        @if(! $stageIsLost)
                            <button wire:click="markLost"
                                    wire:confirm="Mark this lead as Lost?"
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-700 transition hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400 dark:hover:bg-red-900/30">
                                <x-heroicon-m-x-circle class="h-4 w-4 shrink-0" />
                                Mark as Lost
                            </button>
                        @endif

                        {{-- Project: convert or view --}}
                        @if($hasProject && $record->project)
                            @php
                                try {
                                    $projectUrl = \App\Filament\Resources\ProjectResource::getUrl('edit', ['record' => $record->project]);
                                } catch (\Exception $e) {
                                    $projectUrl = '#';
                                }
                            @endphp
                            <a href="{{ $projectUrl }}"
                               class="flex w-full items-center gap-2.5 rounded-xl border border-purple-200 bg-purple-50 px-4 py-2.5 text-sm font-medium text-purple-700 transition hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-400 dark:hover:bg-purple-900/30">
                                <x-heroicon-m-folder-open class="h-4 w-4 shrink-0" />
                                View Project
                            </a>
                        @else
                            <button wire:click="convertToProject"
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-purple-200 bg-purple-50 px-4 py-2.5 text-sm font-medium text-purple-700 transition hover:bg-purple-100 dark:border-purple-800 dark:bg-purple-900/20 dark:text-purple-400 dark:hover:bg-purple-900/30">
                                <x-heroicon-m-folder-plus class="h-4 w-4 shrink-0" />
                                Convert to Project
                            </button>
                        @endif

                        {{-- Assign to self --}}
                        <button wire:click="assignToSelf"
                                class="flex w-full items-center gap-2.5 rounded-xl border border-gray-200 px-4 py-2.5 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-700 dark:text-gray-300 dark:hover:bg-gray-800">
                            <x-heroicon-m-user-plus class="h-4 w-4 shrink-0 text-teal-500" />
                            Assign to Me
                        </button>

                        {{-- Send Email --}}
                        <button wire:click="openEmailModal"
                                class="flex w-full items-center gap-2.5 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-medium text-blue-700 transition hover:bg-blue-100 dark:border-blue-800 dark:bg-blue-900/20 dark:text-blue-400 dark:hover:bg-blue-900/30">
                            <x-heroicon-m-envelope class="h-4 w-4 shrink-0" />
                            Send Email
                        </button>

                        {{-- Send SMS --}}
                        <button wire:click="openSmsModal"
                                class="flex w-full items-center gap-2.5 rounded-xl border border-green-200 bg-green-50 px-4 py-2.5 text-sm font-medium text-green-700 transition hover:bg-green-100 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400 dark:hover:bg-green-900/30">
                            <x-heroicon-m-device-phone-mobile class="h-4 w-4 shrink-0" />
                            Send SMS
                        </button>

                        {{-- ── Proposal ────────────────────────────────── --}}
                        @if($existingQuote)
                            @php
                                $qStatus    = $existingQuote->status;
                                $isDraft    = $qStatus === 'draft';
                                $isSent     = $qStatus === 'sent';
                                $isAccepted = $qStatus === 'accepted';
                                $isRejected = in_array($qStatus, ['rejected', 'expired']);
                            @endphp
                            <div @class([
                                    'rounded-xl border p-3',
                                    'border-amber-200  bg-amber-50/60  dark:border-amber-800  dark:bg-amber-900/10'  => $isDraft,
                                    'border-blue-200   bg-blue-50      dark:border-blue-800   dark:bg-blue-900/10'   => $isSent,
                                    'border-green-200  bg-green-50     dark:border-green-800  dark:bg-green-900/10'  => $isAccepted,
                                    'border-red-200    bg-red-50       dark:border-red-800    dark:bg-red-900/10'    => $isRejected,
                                    'border-gray-200   bg-gray-50      dark:border-gray-700   dark:bg-gray-800/20'   => !$isDraft && !$isSent && !$isAccepted && !$isRejected,
                                ])>
                                <div class="mb-2 flex items-center justify-between">
                                    <span class="flex items-center gap-1.5 text-xs font-semibold uppercase tracking-wide
                                        @if($isDraft) text-amber-700 dark:text-amber-400
                                        @elseif($isSent) text-blue-700 dark:text-blue-400
                                        @elseif($isAccepted) text-green-700 dark:text-green-400
                                        @else text-red-700 dark:text-red-400 @endif">
                                        <x-heroicon-m-clipboard-document-list class="h-3.5 w-3.5" />
                                        {{ $existingQuote->number }}
                                    </span>
                                    <span class="rounded-md px-1.5 py-0.5 text-xs font-medium
                                        @if($isDraft) bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400
                                        @elseif($isSent) bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400
                                        @elseif($isAccepted) bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400
                                        @else bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 @endif">
                                        {{ ucfirst($qStatus) }}
                                    </span>
                                </div>
                                <p class="mb-2.5 text-xs text-gray-500 dark:text-gray-400">
                                    Total: <strong class="text-gray-800 dark:text-gray-100">
                                        {{ number_format((float) $existingQuote->total, 2) }} {{ $existingQuote->currency }}
                                    </strong>
                                </p>
                                <div class="flex gap-2">
                                    @if($isDraft)
                                        <button wire:click="openProposalBuilder"
                                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-amber-300 bg-white px-2 py-1.5 text-xs font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-700 dark:bg-gray-800 dark:text-amber-400 dark:hover:bg-amber-900/20">
                                            <x-heroicon-m-pencil class="h-3 w-3" /> Edit
                                        </button>
                                        <button wire:click="sendExistingDraft"
                                                wire:loading.attr="disabled"
                                                wire:confirm="Send this proposal to the client?"
                                                class="flex flex-1 items-center justify-center gap-1 rounded-lg bg-primary-600 px-2 py-1.5 text-xs font-medium text-white transition hover:bg-primary-700 disabled:opacity-60">
                                            <x-heroicon-m-paper-airplane class="h-3 w-3" /> Send
                                        </button>
                                    @else
                                        @php
                                            try { $quoteUrl = \App\Filament\Resources\QuoteResource::getUrl('edit', ['record' => $existingQuote]); }
                                            catch (\Exception $e) { $quoteUrl = '#'; }
                                        @endphp
                                        <a href="{{ $quoteUrl }}"
                                           class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <x-heroicon-m-eye class="h-3 w-3" /> View Quote
                                        </a>
                                        <button wire:click="openProposalBuilder"
                                                class="flex flex-1 items-center justify-center gap-1 rounded-lg border border-gray-300 px-2 py-1.5 text-xs font-medium text-gray-600 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-700">
                                            <x-heroicon-m-plus class="h-3 w-3" /> New Draft
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @else
                            <button wire:click="openProposalBuilder"
                                    class="flex w-full items-center gap-2.5 rounded-xl border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-medium text-indigo-700 transition hover:bg-indigo-100 dark:border-indigo-800 dark:bg-indigo-900/20 dark:text-indigo-400 dark:hover:bg-indigo-900/30">
                                <x-heroicon-m-clipboard-document-list class="h-4 w-4 shrink-0" />
                                Build Proposal
                            </button>
                        @endif

                    </div>
                </div>
            </div>

            {{-- ── Client & Contact ──────────────────────────────── --}}
            @if($record->client || $record->contact)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <x-heroicon-m-building-office-2 class="h-4 w-4" />
                            Client & Contact
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">

                        @if($record->client)
                            <div class="px-5 py-4 space-y-3">
                                <div>
                                    <p class="mb-0.5 text-xs text-gray-400 dark:text-gray-500">Company</p>
                                    @php
                                        try {
                                            $clientUrl = \App\Filament\Resources\ClientResource::getUrl('view', ['record' => $record->client]);
                                        } catch (\Exception $e) {
                                            $clientUrl = '#';
                                        }
                                    @endphp
                                    <a href="{{ $clientUrl }}"
                                       class="text-sm font-semibold text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $record->client->company_name }}
                                    </a>
                                    @if(!empty($record->client->city))
                                        <p class="mt-0.5 text-xs text-gray-400">{{ $record->client->city }}</p>
                                    @endif
                                </div>
                                @if(!empty($record->client->primary_contact_email))
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-400 dark:text-gray-500">Email</p>
                                        <a href="mailto:{{ $record->client->primary_contact_email }}"
                                           class="text-sm text-primary-600 hover:underline dark:text-primary-400">
                                            {{ $record->client->primary_contact_email }}
                                        </a>
                                    </div>
                                @endif
                                @if(!empty($record->client->primary_contact_phone))
                                    <div>
                                        <p class="mb-0.5 text-xs text-gray-400 dark:text-gray-500">Phone</p>
                                        <a href="tel:{{ $record->client->primary_contact_phone }}"
                                           class="text-sm text-gray-900 hover:underline dark:text-white">
                                            {{ $record->client->primary_contact_phone }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        @endif

                        @if($record->contact)
                            <div class="px-5 py-4 space-y-1">
                                <p class="mb-1 text-xs text-gray-400 dark:text-gray-500">Contact Person</p>
                                <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->contact->full_name }}</p>
                                @if(!empty($record->contact->position))
                                    <p class="text-xs text-gray-400">{{ $record->contact->position }}</p>
                                @endif
                                @if(!empty($record->contact->email))
                                    <a href="mailto:{{ $record->contact->email }}"
                                       class="block text-xs text-primary-600 hover:underline dark:text-primary-400">
                                        {{ $record->contact->email }}
                                    </a>
                                @endif
                            </div>
                        @endif

                    </div>
                </div>
            @endif

            {{-- ── Calculator Data ───────────────────────────────── --}}
            @if(!empty($record->calculator_data) && is_array($record->calculator_data) && count($record->calculator_data) > 0)
                <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <div class="border-b border-gray-100 px-5 py-4 dark:border-gray-800">
                        <h2 class="flex items-center gap-2 text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">
                            <x-heroicon-m-calculator class="h-4 w-4" />
                            Calculator Data
                        </h2>
                    </div>
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($record->calculator_data as $key => $value)
                            @if($value !== null && $value !== '' && $value !== false)
                                <div class="flex items-start justify-between gap-3 px-5 py-2.5">
                                    <span class="shrink-0 text-xs capitalize text-gray-500 dark:text-gray-400">{{ str_replace('_', ' ', $key) }}</span>
                                    <span class="max-w-[60%] break-words text-right text-xs font-medium text-gray-900 dark:text-white">
                                        @if(is_array($value))
                                            {{ implode(', ', $value) }}
                                        @elseif(is_bool($value))
                                            {{ $value ? 'Yes' : 'No' }}
                                        @else
                                            {{ $value }}
                                        @endif
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

        </div>{{-- end right column --}}

    </div>

{{-- ═══════════════════════════════════════════════════════════════════
     Email Modal
════════════════════════════════════════════════════════════════════ --}}
<div x-data
     x-show="$wire.showEmailModal"
     x-cloak
     @keydown.escape.window="$wire.set('showEmailModal', false)"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

    <div @click.stop
         x-show="$wire.showEmailModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100 dark:bg-blue-900/40">
                    <x-heroicon-m-envelope class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                </span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Send Email</h3>
                @if($record->client?->primary_contact_email)
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $record->client->primary_contact_email }}</span>
                @endif
            </div>
            <button wire:click="$set('showEmailModal', false)"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <x-heroicon-m-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Template selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Template
                    <span class="ml-1 text-xs font-normal text-gray-400">(auto-fills subject & body)</span>
                </label>
                <select wire:model.live="emailTemplateId"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">— Select a template —</option>
                    @foreach($emailTemplates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Subject --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Subject <span class="text-red-500">*</span>
                </label>
                <input wire:model="emailSubject"
                       type="text"
                       placeholder="Email subject..."
                       class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500" />
                @error('emailSubject')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Body (TinyMCE) --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Message <span class="text-red-500">*</span>
                </label>

                <div wire:ignore
                     x-data="{
                         editor: null,
                         init() {
                             const self = this;
                             const apiKey = '{{ config('services.tinymce.api_key') }}';
                             const doInit = () => {
                                 const isDark = document.documentElement.classList.contains('dark');
                                 window.tinymce.init({
                                     selector: '#email-body-tinymce',
                                     height: 280,
                                     menubar: false,
                                     plugins: 'advlist autolink lists link charmap searchreplace visualblocks code fullscreen insertdatetime table wordcount',
                                     toolbar: 'undo redo | styles | bold italic underline | forecolor | alignleft aligncenter alignright | bullist numlist | link | removeformat | fullscreen',
                                     skin: isDark ? 'oxide-dark' : 'oxide',
                                     content_style: isDark
                                         ? 'body { background:#111827; color:#e2e8f0; font-family:inherit; font-size:14px; margin:8px; }'
                                         : 'body { font-family:inherit; font-size:14px; margin:8px; }',
                                     promotion: false,
                                     branding: false,
                                     setup(editor) {
                                         self.editor = editor;
                                         editor.on('init', () => {
                                             editor.setContent(self.$wire.get('emailBody') ?? '');
                                         });
                                         editor.on('change input keyup', () => {
                                             self.$wire.set('emailBody', editor.getContent(), false);
                                         });
                                     },
                                 });
                                 // Watch server-side emailBody changes (e.g. template selection)
                                 self.$wire.$watch('emailBody', (val) => {
                                     if (self.editor && self.editor.getContent() !== val) {
                                         self.editor.setContent(val ?? '');
                                     }
                                 });
                             };
                             if (window.tinymce) {
                                 doInit();
                             } else {
                                 let cdn = document.getElementById('tinymce-cdn-script');
                                 if (!cdn) {
                                     cdn = document.createElement('script');
                                     cdn.id = 'tinymce-cdn-script';
                                     cdn.src = `https://cdn.tiny.cloud/1/${apiKey}/tinymce/7/tinymce.min.js`;
                                     cdn.referrerPolicy = 'origin';
                                     document.head.appendChild(cdn);
                                 }
                                 cdn.addEventListener('load', doInit);
                             }
                         },
                         destroy() {
                             if (this.editor) { this.editor.destroy(); this.editor = null; }
                         }
                     }"
                     x-init="init()"
                     x-on:livewire:navigated.window="destroy()">
                    <textarea id="email-body-tinymce"></textarea>
                </div>

                @error('emailBody')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            {{-- Attachments --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Attachments
                    <span class="ml-1 text-xs font-normal text-gray-400">(max 5 files · 10 MB each)</span>
                </label>

                <label class="flex cursor-pointer items-center gap-2.5 rounded-xl border-2 border-dashed border-gray-300 px-4 py-3 text-sm text-gray-500 transition hover:border-primary-400 hover:text-primary-600 dark:border-gray-600 dark:text-gray-400 dark:hover:border-primary-500 dark:hover:text-primary-400">
                    <x-heroicon-m-paper-clip class="h-5 w-5 shrink-0" />
                    <span>Click to attach files…</span>
                    <input type="file"
                           multiple
                           wire:model="emailAttachments"
                           class="sr-only" />
                </label>

                @error('emailAttachments')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                @error('emailAttachments.*')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror

                {{-- Uploading indicator --}}
                <div wire:loading wire:target="emailAttachments" class="mt-2 flex items-center gap-2 text-xs text-gray-400">
                    <svg class="h-3.5 w-3.5 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    Uploading…
                </div>

                @if(count($emailAttachments) > 0)
                    <ul class="mt-2 space-y-1.5">
                        @foreach($emailAttachments as $i => $attachment)
                            <li class="flex items-center justify-between rounded-lg border border-gray-200 bg-gray-50 px-3 py-2 dark:border-gray-700 dark:bg-gray-800">
                                <div class="flex items-center gap-2 min-w-0">
                                    <x-heroicon-m-document class="h-4 w-4 shrink-0 text-gray-400" />
                                    <span class="truncate text-xs text-gray-700 dark:text-gray-300">
                                        {{ $attachment->getClientOriginalName() }}
                                    </span>
                                    <span class="shrink-0 text-xs text-gray-400">
                                        ({{ round($attachment->getSize() / 1024, 0) }} KB)
                                    </span>
                                </div>
                                <button wire:click="removeEmailAttachment({{ $i }})"
                                        type="button"
                                        class="ml-2 shrink-0 rounded-md p-1 text-gray-400 hover:text-red-500 transition">
                                    <x-heroicon-m-x-mark class="h-4 w-4" />
                                </button>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button wire:click="$set('showEmailModal', false)"
                    class="rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Cancel
            </button>
            <button wire:click="sendEmail"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition disabled:opacity-60">
                <x-heroicon-m-paper-airplane class="h-4 w-4" />
                <span wire:loading.remove wire:target="sendEmail">Send Email</span>
                <span wire:loading wire:target="sendEmail">Sending…</span>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     SMS MODAL
══════════════════════════════════════════════════════════════════════════ --}}
<div x-data
     x-show="$wire.showSmsModal"
     x-cloak
     @keydown.escape.window="$wire.set('showSmsModal', false)"
     class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

    <div @click.stop
         x-show="$wire.showSmsModal"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 translate-y-2"
         class="w-full max-w-lg rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4">
            <div class="flex items-center gap-2.5">
                <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-100 dark:bg-green-900/40">
                    <x-heroicon-m-device-phone-mobile class="h-4 w-4 text-green-600 dark:text-green-400" />
                </span>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">Send SMS</h3>
                @if($record->client?->primary_contact_phone)
                    <span class="rounded-full bg-gray-100 px-2.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">{{ $record->client->primary_contact_phone }}</span>
                @endif
            </div>
            <button wire:click="$set('showSmsModal', false)"
                    class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition">
                <x-heroicon-m-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Template selector --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                    Template
                    <span class="ml-1 text-xs font-normal text-gray-400">(fills message below)</span>
                </label>
                <select wire:model.live="smsTemplateId"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500">
                    <option value="">— Select a template —</option>
                    @foreach($smsTemplates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Message --}}
            <div x-data="{ count: 0 }" x-init="count = $refs.msg.value.length">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <span class="text-xs text-gray-400" x-text="count + ' chars / ' + Math.ceil(count / 160) + ' SMS'"></span>
                </div>
                <textarea wire:model="smsMessage"
                          x-ref="msg"
                          x-on:input="count = $event.target.value.length"
                          rows="5"
                          maxlength="1600"
                          placeholder="Type your message or select a template above..."
                          class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-y"></textarea>
                @error('smsMessage')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            @unless($record->client?->primary_contact_phone)
                <div class="rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 px-4 py-3 text-sm text-amber-700 dark:text-amber-400">
                    No phone number on file for this client — SMS cannot be sent.
                </div>
            @endunless

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-gray-200 dark:border-gray-700 px-6 py-4">
            <button wire:click="$set('showSmsModal', false)"
                    class="rounded-xl px-4 py-2 text-sm font-medium text-gray-600 hover:bg-gray-100 dark:text-gray-400 dark:hover:bg-gray-800 transition">
                Cancel
            </button>
            <button wire:click="sendSmsFromLead"
                    wire:loading.attr="disabled"
                    @unless($record->client?->primary_contact_phone) disabled @endunless
                    class="flex items-center gap-2 rounded-xl bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span wire:loading.remove wire:target="sendSmsFromLead">
                    <x-heroicon-m-paper-airplane class="h-4 w-4 inline mr-1" />
                    Send SMS
                </span>
                <span wire:loading wire:target="sendSmsFromLead">Sending…</span>
            </button>
        </div>
    </div>
</div>

{{-- ══════════════════════════════════════════════════════════════════════════
     PROPOSAL BUILDER MODAL
══════════════════════════════════════════════════════════════════════════ --}}
<div
    x-data
    x-show="$wire.showProposalModal"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.set('showProposalModal', false)"
    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-gray-900/60 p-4 backdrop-blur-sm"
    style="display: none;">

    <div
        x-show="$wire.showProposalModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="my-8 w-full max-w-7xl overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/50">
            <div>
                <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                    <x-heroicon-m-clipboard-document-list class="h-5 w-5 text-indigo-500" />
                    Proposal Builder
                    @if($proposalQuoteId)
                        <span class="text-sm font-normal text-gray-400">· editing draft</span>
                    @endif
                </h3>
                <p class="mt-0.5 text-xs text-gray-400 dark:text-gray-500">{{ $record->title }}</p>
            </div>
            <button wire:click="$set('showProposalModal', false)"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-200 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-200">
                <x-heroicon-m-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Settings row --}}
        <div class="grid grid-cols-4 gap-4 border-b border-gray-200 bg-gray-50/50 px-6 py-3 dark:border-gray-700 dark:bg-gray-800/30">
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Currency</label>
                <select wire:model.live="proposalCurrency"
                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="GBP">£ GBP</option>
                    <option value="EUR">€ EUR</option>
                    <option value="USD">$ USD</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">VAT Rate</label>
                <div class="flex items-center gap-1">
                    <input wire:model.live="proposalVatRate" type="number" min="0" max="100" step="0.5"
                           class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                    <span class="text-sm text-gray-400">%</span>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Discount</label>
                <input wire:model.live="proposalDiscount" type="number" min="0" step="10" placeholder="0"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-gray-500 dark:text-gray-400">Valid Until</label>
                <input wire:model.live="proposalValidUntil" type="date"
                       class="w-full rounded-lg border border-gray-300 bg-white px-3 py-1.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
            </div>
        </div>

        {{-- Body --}}
        <div class="flex gap-0" style="min-height: 380px;">

            {{-- Line items (main) --}}
            <div class="flex-1 overflow-x-auto">

                {{-- Table header --}}
                <div class="grid grid-cols-11 border-b border-gray-200 bg-gray-50/70 px-6 py-2 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:border-gray-700 dark:bg-gray-800/30">
                    <div class="col-span-6">Description</div>
                    <div class="col-span-3">Details</div>
                    <div class="col-span-1 text-right">Qty</div>
                    <div class="col-span-1 text-right">Unit Price</div>
                </div>

                {{-- Items --}}
                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach($proposalItems as $i => $item)
                        <div class="relative grid grid-cols-11 items-start gap-2 py-3 pl-6 pr-10">

                            {{-- Description with autocomplete --}}
                            <div
                                class="relative col-span-6"
                                x-data="{
                                    open: false,
                                    suggestions: [],
                                    timer: null,
                                    localVal: @js($item['description'] ?? ''),
                                    fetchSuggestions(val) {
                                        clearTimeout(this.timer);
                                        this.timer = setTimeout(async () => {
                                            this.suggestions = await $wire.getServiceSuggestions(val);
                                            this.open = this.suggestions.length > 0;
                                        }, 250);
                                    },
                                    pick(s) {
                                        this.localVal = s.label;
                                        $wire.set('proposalItems.{{ $i }}.description', s.label);
                                        $wire.set('proposalItems.{{ $i }}.unit_price', s.base_cost);
                                        if (s.description) {
                                            $wire.set('proposalItems.{{ $i }}.details', s.description);
                                        }
                                        this.open = false;
                                    },
                                    syncToWire() {
                                        $wire.set('proposalItems.{{ $i }}.description', this.localVal);
                                    }
                                }"
                                @click.outside="open = false"
                            >
                                <input
                                    type="text"
                                    x-model="localVal"
                                    @input.debounce.300ms="fetchSuggestions(localVal)"
                                    @focus="fetchSuggestions(localVal)"
                                    @blur="syncToWire()"
                                    @keydown.escape="open = false"
                                    placeholder="Service or item description…"
                                    class="h-[54px] w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />

                                {{-- Suggestions dropdown --}}
                                <div
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    class="absolute left-0 top-full z-50 mt-1 w-full overflow-hidden rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800">
                                    <template x-for="(s, idx) in suggestions" :key="idx">
                                        <button type="button"
                                                @click="pick(s)"
                                                class="flex w-full items-center gap-3 border-b border-gray-100 px-3 py-2 text-left text-sm last:border-0 hover:bg-primary-50 dark:border-gray-700 dark:hover:bg-primary-900/20">
                                            <div class="min-w-0 flex-1">
                                                <div class="flex items-center gap-2">
                                                    <p class="truncate font-medium text-gray-900 dark:text-white" x-text="s.label"></p>
                                                    <span x-show="s.category"
                                                          x-text="s.category"
                                                          class="shrink-0 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500 dark:bg-gray-700 dark:text-gray-400"></span>
                                                </div>
                                                <p x-show="s.description" class="truncate text-xs text-gray-400 mt-0.5" x-text="s.description"></p>
                                            </div>
                                            <span class="shrink-0 rounded bg-primary-50 px-1.5 py-0.5 text-xs font-semibold text-primary-700 dark:bg-primary-900/30 dark:text-primary-400"
                                                  x-text="'£' + Number(s.base_cost).toFixed(2)"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>

                            <div class="col-span-3">
                                <textarea wire:model.blur="proposalItems.{{ $i }}.details"
                                          rows="2"
                                          placeholder="Optional details…"
                                          class="h-[54px] w-full resize-none rounded-lg border border-gray-300 bg-white px-3 py-2 text-xs text-gray-600 placeholder-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"></textarea>
                            </div>
                            <div class="col-span-1">
                                <input wire:model.live="proposalItems.{{ $i }}.quantity"
                                       type="number" min="0.01" step="0.5"
                                       class="h-[54px] w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-right text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            </div>
                            <div class="col-span-1">
                                <input wire:model.live="proposalItems.{{ $i }}.unit_price"
                                       type="number" min="0" step="10"
                                       class="h-[54px] w-full rounded-lg border border-gray-300 bg-white px-2 py-2 text-right text-sm text-gray-900 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                            </div>
                            @if(count($proposalItems) > 1)
                            <button wire:click="removeProposalItem({{ $i }})"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 rounded p-1 text-gray-300 transition hover:text-red-500 dark:text-gray-600 dark:hover:text-red-400">
                                <x-heroicon-m-x-mark class="h-4 w-4" />
                            </button>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Add item --}}
                <div class="px-6 py-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                    <button wire:click="addProposalItem"
                            class="flex items-center gap-1.5 rounded-lg border border-dashed border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-500 transition hover:border-primary-400 hover:text-primary-600 dark:border-gray-600 dark:text-gray-400 dark:hover:border-primary-500 dark:hover:text-primary-400">
                        <x-heroicon-m-plus class="h-3.5 w-3.5" />
                        Add line item
                    </button>
                </div>

                {{-- Notes / Terms --}}
                <div class="grid grid-cols-2 gap-4 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Notes (visible to client)</label>
                        <textarea wire:model.blur="proposalNotes" rows="3" placeholder="Any extra notes for the client…"
                                  class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-500 dark:text-gray-400">Terms & Conditions</label>
                        <textarea wire:model.blur="proposalTerms" rows="3"
                                  class="w-full resize-none rounded-xl border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                    </div>
                </div>

            </div>

            {{-- Totals sidebar --}}
            <div class="w-52 shrink-0 border-l border-gray-200 bg-gray-50/60 p-5 dark:border-gray-700 dark:bg-gray-800/30">
                @php
                    $pSubtotal  = 0;
                    foreach ($proposalItems as $pi) {
                        $pSubtotal += round((float)($pi['quantity'] ?? 0) * (float)($pi['unit_price'] ?? 0), 2);
                    }
                    $pDiscount     = (float)($proposalDiscount ?: 0);
                    $pVatRate      = (float)($proposalVatRate ?: 0);
                    $pVat          = round(($pSubtotal - $pDiscount) * ($pVatRate / 100), 2);
                    $pTotal        = $pSubtotal - $pDiscount + $pVat;
                    $pCurrSym      = match($proposalCurrency) { 'GBP' => '£', 'EUR' => '€', 'USD' => '$', default => $proposalCurrency . ' ' };
                @endphp

                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">Summary</p>

                <div class="space-y-2 text-sm">
                    <div class="flex justify-between text-gray-600 dark:text-gray-300">
                        <span>Subtotal</span>
                        <span>{{ $pCurrSym }}{{ number_format($pSubtotal, 2) }}</span>
                    </div>
                    @if($pDiscount > 0)
                        <div class="flex justify-between text-green-600 dark:text-green-400">
                            <span>Discount</span>
                            <span>–{{ $pCurrSym }}{{ number_format($pDiscount, 2) }}</span>
                        </div>
                    @endif
                    @if($pVatRate > 0)
                        <div class="flex justify-between text-gray-500 dark:text-gray-400">
                            <span>VAT ({{ $pVatRate }}%)</span>
                            <span>{{ $pCurrSym }}{{ number_format($pVat, 2) }}</span>
                        </div>
                    @endif
                    <div class="mt-2 border-t border-gray-200 pt-2 dark:border-gray-600">
                        <div class="flex justify-between text-base font-bold text-gray-900 dark:text-white">
                            <span>Total</span>
                            <span>{{ $pCurrSym }}{{ number_format($pTotal, 2) }}</span>
                        </div>
                    </div>
                </div>

                @if($proposalValidUntil)
                    <div class="mt-4 rounded-lg bg-white px-3 py-2 text-xs dark:bg-gray-800">
                        <p class="text-gray-400 dark:text-gray-500">Valid until</p>
                        <p class="font-medium text-gray-700 dark:text-gray-200">
                            {{ \Carbon\Carbon::parse($proposalValidUntil)->format('d M Y') }}
                        </p>
                    </div>
                @endif
            </div>

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-between border-t border-gray-200 bg-gray-50/50 px-6 py-4 dark:border-gray-700 dark:bg-gray-800/30">
            <button wire:click="$set('showProposalModal', false)"
                    class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </button>
            <div class="flex items-center gap-3">
                <button wire:click="saveProposalDraft"
                        wire:loading.attr="disabled"
                        wire:target="saveProposalDraft"
                        class="flex items-center gap-2 rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 disabled:opacity-60 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                    <x-heroicon-m-document-arrow-down class="h-4 w-4" />
                    <span wire:loading.remove wire:target="saveProposalDraft">Save Draft</span>
                    <span wire:loading wire:target="saveProposalDraft">Saving…</span>
                </button>
                <button wire:click="sendProposal"
                        wire:loading.attr="disabled"
                        wire:target="sendProposal"
                        wire:confirm="Send this proposal to the client? The lead will move to 'Proposal Sent' stage."
                        class="flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                    <x-heroicon-m-paper-airplane class="h-4 w-4" />
                    <span wire:loading.remove wire:target="sendProposal">Send Proposal</span>
                    <span wire:loading wire:target="sendProposal">Sending…</span>
                </button>
            </div>
        </div>

    </div>
</div>

<div
    x-data
    x-show="$wire.showChecklistModal"
    x-transition:enter="ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @keydown.escape.window="$wire.set('showChecklistModal', false)"
    class="fixed inset-0 z-50 flex items-center justify-center bg-gray-900/60 p-4 backdrop-blur-sm"
    style="display: none;">

    <div
        x-show="$wire.showChecklistModal"
        x-transition:enter="ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-200 dark:bg-gray-900 dark:ring-gray-700">

        {{-- Header --}}
        <div class="flex items-center justify-between border-b border-gray-200 px-6 py-4 dark:border-gray-700">
            <h3 class="flex items-center gap-2 text-base font-semibold text-gray-900 dark:text-white">
                <x-heroicon-m-pencil-square class="h-4 w-4 text-amber-500" />
                {{ $checklistModalLabel }}
            </h3>
            <button wire:click="$set('showChecklistModal', false)"
                    class="rounded-lg p-1 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-800 dark:hover:text-gray-200">
                <x-heroicon-m-x-mark class="h-5 w-5" />
            </button>
        </div>

        {{-- Body --}}
        <div class="px-6 py-5 space-y-4">

            {{-- Budget range (has_value) --}}
            @if($checklistModalCondition === 'has_value')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Enter the client's budget range. The maximum will also be set as the deal value.
                </p>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Min Budget</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">£</span>
                            <input wire:model="modalBudgetMin"
                                   type="number" min="0" step="100" placeholder="0"
                                   class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-7 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                        </div>
                    </div>
                    <div>
                        <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Max Budget</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-400">£</span>
                            <input wire:model="modalBudgetMax"
                                   type="number" min="0" step="100" placeholder="0"
                                   class="w-full rounded-xl border border-gray-300 bg-white py-2.5 pl-7 pr-3 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                        </div>
                    </div>
                </div>
            @endif

            {{-- Assign owner (has_assignee) --}}
            @if($checklistModalCondition === 'has_assignee')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Select a team member to assign this lead to.
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Assign To</label>
                    <select wire:model="modalAssignedTo"
                            class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                        <option value="">— Unassigned —</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" @selected((int)$modalAssignedTo === $user->id)>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            {{-- Expected close date (has_expected_close) --}}
            @if($checklistModalCondition === 'has_expected_close')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Set the expected date for closing this lead.
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Expected Close Date</label>
                    <input wire:model="modalExpectedClose"
                           type="date"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                </div>
            @endif

            {{-- Quick note (has_notes) --}}
            @if($checklistModalCondition === 'has_notes')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Add a note about this lead. It will appear in the Notes panel.
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Note</label>
                    <textarea wire:model="modalNoteText"
                              rows="4"
                              placeholder="Write your note here…"
                              class="w-full resize-none rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                </div>
            @endif

            {{-- Client phone (has_phone) --}}
            @if($checklistModalCondition === 'has_phone')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Update the primary contact phone number for this client.
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Phone Number</label>
                    <input wire:model="modalPhone"
                           type="tel"
                           placeholder="+44 000 000 0000"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                </div>
            @endif

            {{-- Client email (has_email) --}}
            @if($checklistModalCondition === 'has_email')
                <p class="text-xs text-gray-500 dark:text-gray-400">
                    Update the primary contact email address for this client.
                </p>
                <div>
                    <label class="mb-1.5 block text-xs font-medium text-gray-600 dark:text-gray-400">Email Address</label>
                    <input wire:model="modalEmail"
                           type="email"
                           placeholder="client@company.com"
                           class="w-full rounded-xl border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:border-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-500/20 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                </div>
            @endif

        </div>

        {{-- Footer --}}
        <div class="flex items-center justify-end gap-3 border-t border-gray-200 px-6 py-4 dark:border-gray-700">
            <button wire:click="$set('showChecklistModal', false)"
                    class="rounded-xl border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-50 dark:border-gray-600 dark:text-gray-200 dark:hover:bg-gray-700">
                Cancel
            </button>
            <button wire:click="saveChecklistModal"
                    wire:loading.attr="disabled"
                    class="flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white transition hover:bg-primary-700 disabled:opacity-60">
                <x-heroicon-m-check class="h-4 w-4" />
                <span wire:loading.remove wire:target="saveChecklistModal">Save</span>
                <span wire:loading wire:target="saveChecklistModal">Saving…</span>
            </button>
        </div>
    </div>
</div>

</x-filament-panels::page>
