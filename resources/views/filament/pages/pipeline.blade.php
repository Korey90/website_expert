<x-filament-panels::page>
    <div class="space-y-4">

        {{-- Summary bar --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($stages as $stage)
                @php $t = $totals[$stage->id] ?? ['count' => 0, 'total' => 0]; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $stage->name }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $t['count'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">£{{ number_format($t['total'] ?? 0, 0) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Kanban board --}}
        @php $stageIds = $stages->pluck('id')->toArray(); @endphp
        <div class="flex gap-4 overflow-x-auto pb-4">
            @foreach($stages as $stage)
                @php
                    $stageLeads = $leads[$stage->id] ?? collect();
                    $colors = [
                        'New Lead'      => 'bg-blue-500',
                        'Contacted'     => 'bg-yellow-500',
                        'Proposal Sent' => 'bg-purple-500',
                        'Negotiation'   => 'bg-orange-500',
                        'Won'           => 'bg-green-500',
                        'Lost'          => 'bg-red-500',
                    ];
                    $dot = $colors[$stage->name] ?? 'bg-gray-400';
                @endphp
                <div class="flex-shrink-0 w-72">
                    {{-- Column header --}}
                    <div class="mb-3 flex items-center justify-between rounded-lg bg-gray-100 px-3 py-2 dark:bg-gray-800">
                        <div class="flex items-center gap-2">
                            <span class="inline-block h-2.5 w-2.5 rounded-full {{ $dot }}"></span>
                            <span class="text-sm font-semibold text-gray-700 dark:text-gray-200">{{ $stage->name }}</span>
                        </div>
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-bold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                            {{ $stageLeads->count() }}
                        </span>
                    </div>

                    {{-- Cards --}}
                    <div class="space-y-2">
                        @forelse($stageLeads as $lead)
                            @php
                                $currentIdx = array_search($lead->pipeline_stage_id, $stageIds);
                                $isFirst    = $currentIdx === 0;
                                $isLast     = $currentIdx === count($stageIds) - 1;
                            @endphp

                            <div x-data="{ open: false }"
                                 class="relative rounded-xl border border-gray-200 bg-white shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-gray-700 dark:bg-gray-900 dark:hover:border-primary-600">

                                {{-- Clickable card body --}}
                                <a href="{{ route('filament.admin.resources.leads.view', $lead) }}"
                                   class="block p-3 pr-9 rounded-xl">
                                    <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ $lead->title }}</p>

                                    @if($lead->client)
                                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400 flex items-center gap-1">
                                            <x-heroicon-m-building-office class="h-3 w-3 shrink-0" />
                                            {{ $lead->client->company_name }}
                                        </p>
                                    @endif

                                    <div class="mt-2 flex items-center justify-between">
                                        @if($lead->value)
                                            <span class="text-xs font-bold text-green-600 dark:text-green-400">
                                                £{{ number_format($lead->value, 0) }}
                                            </span>
                                        @else
                                            <span></span>
                                        @endif

                                        @if($lead->source)
                                            <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
                                                {{ str_replace('_', ' ', $lead->source) }}
                                            </span>
                                        @endif
                                    </div>

                                    @if($lead->expected_close_date)
                                        <p class="mt-1.5 text-xs flex items-center gap-1 {{ $lead->expected_close_date < now() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                            <x-heroicon-m-clock class="h-3 w-3 shrink-0" />
                                            {{ $lead->expected_close_date->format('d M Y') }}
                                        </p>
                                    @endif

                                    @if($lead->assignedTo)
                                        <p class="mt-1 text-xs text-gray-400 dark:text-gray-500 flex items-center gap-1">
                                            <x-heroicon-m-user class="h-3 w-3 shrink-0" />
                                            {{ $lead->assignedTo->name }}
                                        </p>
                                    @endif
                                </a>

                                {{-- 3-dot menu button --}}
                                <div class="absolute top-2 right-2">
                                    <button @click.prevent.stop="open = !open"
                                            class="flex h-6 w-6 items-center justify-center rounded-md text-gray-400 hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-gray-700 dark:hover:text-gray-300 transition">
                                        <x-heroicon-m-ellipsis-vertical class="h-4 w-4" />
                                    </button>

                                    {{-- Dropdown --}}
                                    <div x-show="open"
                                         @click.outside="open = false"
                                         x-cloak
                                         x-transition:enter="transition ease-out duration-100"
                                         x-transition:enter-start="transform opacity-0 scale-95"
                                         x-transition:enter-end="transform opacity-100 scale-100"
                                         x-transition:leave="transition ease-in duration-75"
                                         x-transition:leave-start="transform opacity-100 scale-100"
                                         x-transition:leave-end="transform opacity-0 scale-95"
                                         class="absolute right-0 top-7 z-30 w-52 rounded-xl border border-gray-200 bg-white shadow-xl dark:border-gray-700 dark:bg-gray-800 overflow-hidden">

                                        <div class="py-1 text-sm">

                                            {{-- Open / Edit --}}
                                            <a href="{{ route('filament.admin.resources.leads.edit', $lead) }}"
                                               @click="open = false"
                                               class="flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-pencil-square class="h-4 w-4 text-gray-400 shrink-0" />
                                                Open / Edit
                                            </a>

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Move stage --}}
                                            @if(! $isFirst)
                                            <button wire:click="moveStage({{ $lead->id }}, 'back')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-arrow-left class="h-4 w-4 text-gray-400 shrink-0" />
                                                Move Back
                                            </button>
                                            @endif

                                            @if(! $isLast)
                                            <button wire:click="moveStage({{ $lead->id }}, 'forward')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-arrow-right class="h-4 w-4 text-gray-400 shrink-0" />
                                                Move Forward
                                            </button>
                                            @endif

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Won / Lost --}}
                                            <button wire:click="markWon({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/30">
                                                <x-heroicon-m-check-circle class="h-4 w-4 shrink-0" />
                                                Mark as Won
                                            </button>
                                            <button wire:click="markLost({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                                <x-heroicon-m-x-circle class="h-4 w-4 shrink-0" />
                                                Mark as Lost
                                            </button>

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Convert to Project --}}
                                            <button wire:click="convertToProject({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-folder-plus class="h-4 w-4 text-purple-500 shrink-0" />
                                                Convert to Project
                                            </button>

                                            {{-- Send Email --}}
                                            <button wire:click="openEmailModal({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-envelope class="h-4 w-4 text-blue-500 shrink-0" />
                                                Send Email
                                            </button>

                                            {{-- Notes --}}
                                            <button wire:click="openNoteModal({{ $lead->id }}, '{{ addslashes($lead->title) }}')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500 shrink-0" />
                                                Notes
                                            </button>

                                            {{-- History --}}
                                            <button wire:click="openHistoryModal({{ $lead->id }}, '{{ addslashes($lead->title) }}')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-clock class="h-4 w-4 text-gray-400 shrink-0" />
                                                Activity History
                                            </button>

                                            {{-- Assign to Self --}}
                                            <button wire:click="assignToSelf({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-user-plus class="h-4 w-4 text-indigo-500 shrink-0" />
                                                Assign to Me
                                            </button>

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Delete --}}
                                            <button wire:click="deleteLead({{ $lead->id }})"
                                                    wire:confirm="Delete this lead? This action cannot be undone."
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                                <x-heroicon-m-trash class="h-4 w-4 shrink-0" />
                                                Delete Lead
                                            </button>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                No leads
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
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

                {{-- Body --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5">
                        Message <span class="text-red-500">*</span>
                    </label>
                    <textarea wire:model="emailBody"
                              rows="9"
                              placeholder="Write your message here..."
                              class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none font-mono leading-relaxed"></textarea>
                    @error('emailBody')
                        <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                    @enderror
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

    {{-- ═══════════════════════════════════════════════════════════════════
         Notes Modal
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-data
         x-show="$wire.showNoteModal"
         x-cloak
         @keydown.escape.window="$wire.set('showNoteModal', false)"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

        <div @click.stop
             x-show="$wire.showNoteModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="w-full max-w-2xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col max-h-[88vh]">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-yellow-100 dark:bg-yellow-900/40 shrink-0">
                        <x-heroicon-m-document-text class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Notes</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $noteLeadTitle }}</p>
                    </div>
                </div>
                <button wire:click="$set('showNoteModal', false)"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0 ml-3">
                    <x-heroicon-m-x-mark class="h-5 w-5" />
                </button>
            </div>

            {{-- Note list --}}
            <div class="overflow-y-auto flex-1 px-6 py-4 space-y-3">
                @forelse($leadNotes as $note)
                    <div class="rounded-xl border {{ $note->is_pinned ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700/50 dark:bg-yellow-900/10' : 'border-gray-200 bg-gray-50 dark:border-gray-700/60 dark:bg-gray-800/50' }} p-4">

                        @if($editNoteId === $note->id)
                            {{-- Edit inline --}}
                            <textarea wire:model="editNoteText"
                                      rows="4"
                                      class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-primary-500 resize-y"></textarea>
                            @error('editNoteText')
                                <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                            <div class="mt-2 flex gap-2">
                                <button wire:click="saveEditNote"
                                        class="flex items-center gap-1.5 rounded-lg bg-primary-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-primary-700 transition">
                                    <x-heroicon-m-check class="h-3.5 w-3.5" />
                                    Save
                                </button>
                                <button wire:click="cancelEditNote"
                                        class="rounded-lg border border-gray-300 dark:border-gray-600 px-3 py-1.5 text-xs font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 transition">
                                    Cancel
                                </button>
                            </div>
                        @else
                            {{-- Display mode --}}
                            <p class="text-sm text-gray-800 dark:text-gray-100 whitespace-pre-wrap leading-relaxed">{{ $note->content }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <div class="flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                    @if($note->user)
                                        <span class="flex items-center gap-1">
                                            <x-heroicon-m-user class="h-3 w-3" />
                                            {{ $note->user->name }}
                                        </span>
                                    @endif
                                    <span>{{ $note->created_at->format('d M Y, H:i') }}</span>
                                    @if($note->created_at->ne($note->updated_at))
                                        <span class="text-gray-300 dark:text-gray-600">(edited)</span>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1">
                                    <button wire:click="togglePinNote({{ $note->id }})"
                                            title="{{ $note->is_pinned ? 'Unpin' : 'Pin' }}"
                                            class="rounded-md p-1 {{ $note->is_pinned ? 'text-yellow-500 hover:text-yellow-600' : 'text-gray-300 hover:text-yellow-400' }} transition">
                                        <x-heroicon-m-star class="h-4 w-4" />
                                    </button>
                                    <button wire:click="startEditNote({{ $note->id }})"
                                            title="Edit"
                                            class="rounded-md p-1 text-gray-400 hover:text-blue-500 transition">
                                        <x-heroicon-m-pencil class="h-4 w-4" />
                                    </button>
                                    <button wire:click="deleteNote({{ $note->id }})"
                                            wire:confirm="Delete this note?"
                                            title="Delete"
                                            class="rounded-md p-1 text-gray-400 hover:text-red-500 transition">
                                        <x-heroicon-m-trash class="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="py-10 text-center">
                        <x-heroicon-o-document-text class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="text-sm text-gray-400">No notes yet. Add the first one below.</p>
                    </div>
                @endforelse
            </div>

            {{-- Add new note --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-4 shrink-0">
                <textarea wire:model="newNoteText"
                          rows="3"
                          placeholder="Write a new note..."
                          class="w-full rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 px-3 py-2.5 text-sm text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 resize-none"></textarea>
                @error('newNoteText')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
                <div class="mt-3 flex items-center justify-between gap-3">
                    <button wire:click="$set('showNoteModal', false)"
                            class="rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                        Close
                    </button>
                    <button wire:click="addNote"
                            class="flex items-center gap-2 rounded-xl bg-primary-600 px-5 py-2 text-sm font-semibold text-white hover:bg-primary-700 transition">
                        <x-heroicon-m-plus class="h-4 w-4" />
                        Add Note
                    </button>
                </div>
            </div>
        </div>
    </div>
    {{-- ═══════════════════════════════════════════════════════════════════
         History Modal
    ════════════════════════════════════════════════════════════════════ --}}
    <div x-data
         x-show="$wire.showHistoryModal"
         x-cloak
         @keydown.escape.window="$wire.set('showHistoryModal', false)"
         class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">

        <div @click.stop
             x-show="$wire.showHistoryModal"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             class="w-full max-w-xl rounded-2xl bg-white dark:bg-gray-900 shadow-2xl border border-gray-200 dark:border-gray-700 flex flex-col max-h-[85vh]">

            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-700 px-6 py-4 shrink-0">
                <div class="flex items-center gap-2.5 min-w-0">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-gray-800 shrink-0">
                        <x-heroicon-m-clock class="h-4 w-4 text-gray-500 dark:text-gray-400" />
                    </span>
                    <div class="min-w-0">
                        <h3 class="text-base font-semibold text-gray-900 dark:text-white">Activity History</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $historyLeadTitle }}</p>
                    </div>
                </div>
                <button wire:click="$set('showHistoryModal', false)"
                        class="rounded-lg p-1.5 text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-800 transition shrink-0 ml-3">
                    <x-heroicon-m-x-mark class="h-5 w-5" />
                </button>
            </div>

            {{-- Timeline --}}
            <div class="overflow-y-auto px-6 py-4 flex-1">
                @if($historyActivities->isEmpty())
                    <div class="py-12 text-center">
                        <x-heroicon-o-clock class="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" />
                        <p class="text-sm text-gray-400">No activity recorded yet.</p>
                    </div>
                @else
                    <ol class="relative border-l border-gray-200 dark:border-gray-700 ml-3 space-y-0">
                        @foreach($historyActivities as $activity)
                            <li class="mb-6 ml-5">
                                {{-- Dot --}}
                                <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $activity->bg }}">
                                    <x-dynamic-component :component="$activity->icon" class="h-3 w-3 {{ $activity->color }}" />
                                </span>

                                {{-- Content --}}
                                <div class="rounded-xl border border-gray-100 dark:border-gray-700/60 bg-gray-50 dark:bg-gray-800/50 px-4 py-3">
                                    <p class="text-sm font-medium text-gray-800 dark:text-gray-100 leading-snug">
                                        {{ $activity->description }}
                                    </p>

                                    {{-- Metadata --}}
                                    @if($activity->metadata)
                                        <div class="mt-2 flex flex-wrap gap-1.5">
                                            @foreach($activity->metadata as $key => $val)
                                                @if($val)
                                                    <span class="inline-flex items-center gap-1 rounded-md bg-white dark:bg-gray-700 border border-gray-200 dark:border-gray-600 px-2 py-0.5 text-xs text-gray-500 dark:text-gray-400">
                                                        <span class="font-medium text-gray-400 dark:text-gray-500">{{ str_replace('_', ' ', $key) }}:</span>
                                                        {{ is_array($val) ? implode(', ', $val) : $val }}
                                                    </span>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif

                                    {{-- Footer --}}
                                    <div class="mt-2 flex items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                                        @if($activity->user)
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-user class="h-3 w-3" />
                                                {{ $activity->user->name }}
                                            </span>
                                        @else
                                            <span class="flex items-center gap-1">
                                                <x-heroicon-m-globe-alt class="h-3 w-3" />
                                                System / Website
                                            </span>
                                        @endif
                                        <span>{{ $activity->created_at->format('d M Y, H:i') }}</span>
                                        <span class="ml-auto text-gray-300 dark:text-gray-600">{{ $activity->created_at->diffForHumans() }}</span>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @endif
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-200 dark:border-gray-700 px-6 py-3 shrink-0">
                <button wire:click="$set('showHistoryModal', false)"
                        class="w-full rounded-xl border border-gray-300 dark:border-gray-600 px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                    Close
                </button>
            </div>
        </div>
    </div>

</x-filament-panels::page>

