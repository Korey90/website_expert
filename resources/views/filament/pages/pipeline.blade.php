<x-filament-panels::page>
    @php
        $money = app(\App\Services\Currency\MoneyFormatter::class);
        $summaryMoney = app(\App\Services\Currency\CurrencySummaryFormatter::class);
    @endphp

    <div class="space-y-4">

        {{-- Summary bar --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($stages as $stage)
                @php $t = $totals[$stage->id] ?? ['count' => 0, 'formatted_total' => $summaryMoney->formatGrouped([])]; @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $stage->name }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $t['count'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $t['formatted_total'] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Kanban board --}}
        @php $stageIds = $stages->pluck('id')->toArray(); @endphp
        <div class="admin-pipeline-hint">
            <x-filament::icon icon="heroicon-o-arrows-right-left" class="admin-pipeline-hint-icon" />
            <span>{{ __('admin.pilot.pipeline.scroll_hint') }}</span>
        </div>
        <div
            class="admin-pipeline-scroll flex gap-4 overflow-x-auto pb-4"
            role="region"
            tabindex="0"
            aria-label="{{ __('admin.pilot.pipeline.board_label') }}"
        >
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
                <div class="admin-pipeline-column flex-shrink-0 w-72">
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
                                $canEditLead = \App\Filament\Resources\LeadResource::canEdit($lead);
                                $canDeleteLead = \App\Filament\Resources\LeadResource::canDelete($lead);
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
                                                {{ $money->format($lead->value, $lead->currency) }}
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
                                            aria-label="{{ __('admin.pilot.pipeline.actions.open_menu', ['lead' => $lead->title]) }}"
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

                                            @if($canEditLead)
                                            {{-- Open / Edit --}}
                                            <a href="{{ route('filament.admin.resources.leads.edit', $lead) }}"
                                               @click="open = false"
                                               class="flex items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-pencil-square class="h-4 w-4 text-gray-400 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.open_edit') }}
                                            </a>

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Move stage --}}
                                            @if(! $isFirst)
                                            <button wire:click="moveStage({{ $lead->id }}, 'back')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-arrow-left class="h-4 w-4 text-gray-400 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.move_back') }}
                                            </button>
                                            @endif

                                            @if(! $isLast)
                                            <button wire:click="moveStage({{ $lead->id }}, 'forward')"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-arrow-right class="h-4 w-4 text-gray-400 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.move_forward') }}
                                            </button>
                                            @endif

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Won / Lost --}}
                                            <button wire:click="markWon({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/30">
                                                <x-heroicon-m-check-circle class="h-4 w-4 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.mark_won') }}
                                            </button>
                                            <button wire:click="markLost({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                                <x-heroicon-m-x-circle class="h-4 w-4 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.mark_lost') }}
                                            </button>

                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Convert to Project --}}
                                            <button wire:click="convertToProject({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-folder-plus class="h-4 w-4 text-purple-500 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.convert_project') }}
                                            </button>

                                            {{-- Send Email --}}
                                            <button wire:click="openEmailModal({{ $lead->id }})"
                                                    @click="$dispatch('open-modal', { id: 'pipeline-email' }); open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-envelope class="h-4 w-4 text-blue-500 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.send_email') }}
                                            </button>

                                            {{-- Notes --}}
                                            <button wire:click="openNoteModal({{ $lead->id }})"
                                                    @click="$dispatch('open-modal', { id: 'pipeline-notes' }); open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.notes') }}
                                            </button>
                                            @endif

                                            {{-- History --}}
                                            <button wire:click="openHistoryModal({{ $lead->id }})"
                                                    @click="$dispatch('open-modal', { id: 'pipeline-history' }); open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-clock class="h-4 w-4 text-gray-400 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.history') }}
                                            </button>

                                            {{-- Assign to Self --}}
                                            @if($canEditLead)
                                            <button wire:click="assignToSelf({{ $lead->id }})"
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-gray-700 hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-gray-700">
                                                <x-heroicon-m-user-plus class="h-4 w-4 text-indigo-500 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.assign_self') }}
                                            </button>
                                            @endif

                                            @if($canDeleteLead)
                                            <div class="my-1 border-t border-gray-100 dark:border-gray-700"></div>

                                            {{-- Delete --}}
                                            <button wire:click="deleteLead({{ $lead->id }})"
                                                    wire:confirm="Delete this lead? This action cannot be undone."
                                                    @click="open = false"
                                                    class="flex w-full items-center gap-2.5 px-3 py-2 text-red-600 hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-900/30">
                                                <x-heroicon-m-trash class="h-4 w-4 shrink-0" />
                                                {{ __('admin.pilot.pipeline.actions.delete') }}
                                            </button>
                                            @endif

                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="rounded-xl border border-dashed border-gray-200 py-6 text-center text-xs text-gray-400 dark:border-gray-700">
                                {{ __('admin.pilot.pipeline.no_leads') }}
                            </div>
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <x-filament::modal
        id="pipeline-email"
        width="2xl"
        close-button
        sticky-header
        sticky-footer
        icon="heroicon-o-envelope"
        icon-color="primary"
        :heading="__('admin.pilot.pipeline.modals.email.heading')"
        :description="__('admin.pilot.pipeline.modals.email.description')"
        x-effect="if ($wire.showEmailModal && ! isOpen) open(); if (! $wire.showEmailModal && isOpen) close()"
        x-on:modal-closed="$wire.set('showEmailModal', false)"
    >
        <div class="space-y-4">
            <div>
                <label for="pipeline-email-template" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.pilot.pipeline.modals.email.template') }}
                    <span class="ml-1 text-xs font-normal text-gray-400">{{ __('admin.pilot.pipeline.modals.email.template_hint') }}</span>
                </label>
                <select id="pipeline-email-template" wire:model.live="emailTemplateId"
                        class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white">
                    <option value="">{{ __('admin.pilot.pipeline.modals.email.select_template') }}</option>
                    @foreach($emailTemplates as $tpl)
                        <option value="{{ $tpl->id }}">{{ $tpl->name }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="pipeline-email-subject" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.pilot.pipeline.modals.email.subject') }} <span class="text-red-500">*</span>
                </label>
                <input id="pipeline-email-subject" wire:model="emailSubject" type="text"
                       placeholder="{{ __('admin.pilot.pipeline.modals.email.subject_placeholder') }}"
                       class="w-full rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white" />
                @error('emailSubject')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="pipeline-email-body" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.pilot.pipeline.modals.email.message') }} <span class="text-red-500">*</span>
                </label>
                <textarea id="pipeline-email-body" wire:model="emailBody" rows="9"
                          placeholder="{{ __('admin.pilot.pipeline.modals.email.message_placeholder') }}"
                          class="w-full resize-y rounded-xl border border-gray-300 bg-white px-3 py-2.5 font-mono text-sm leading-relaxed text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                @error('emailBody')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex w-full justify-end gap-3">
                <x-filament::button color="gray" outlined x-on:click="$dispatch('close-modal', { id: 'pipeline-email' })">
                    {{ __('admin.pilot.pipeline.modals.cancel') }}
                </x-filament::button>
                <x-filament::button wire:click="sendEmail" wire:loading.attr="disabled" icon="heroicon-m-paper-airplane">
                    <span wire:loading.remove wire:target="sendEmail">{{ __('admin.pilot.pipeline.modals.email.send') }}</span>
                    <span wire:loading wire:target="sendEmail">{{ __('admin.pilot.pipeline.modals.email.sending') }}</span>
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

    <x-filament::modal
        id="pipeline-notes"
        width="2xl"
        close-button
        sticky-header
        sticky-footer
        icon="heroicon-o-document-text"
        icon-color="warning"
        :heading="__('admin.pilot.pipeline.modals.notes.heading')"
        :description="$noteLeadTitle"
        x-effect="if ($wire.showNoteModal && ! isOpen) open(); if (! $wire.showNoteModal && isOpen) close()"
        x-on:modal-closed="$wire.set('showNoteModal', false)"
    >
        <div class="space-y-3">
            @forelse($leadNotes as $note)
                <article class="rounded-xl border {{ $note->is_pinned ? 'border-yellow-300 bg-yellow-50 dark:border-yellow-700/50 dark:bg-yellow-900/10' : 'border-gray-200 bg-gray-50 dark:border-gray-700/60 dark:bg-gray-800/50' }} p-4">
                    @if($editNoteId === $note->id)
                        <label for="pipeline-note-edit-{{ $note->id }}" class="sr-only">{{ __('admin.pilot.pipeline.modals.notes.edit') }}</label>
                        <textarea id="pipeline-note-edit-{{ $note->id }}" wire:model="editNoteText" rows="4"
                                  class="w-full resize-y rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                        @error('editNoteText')
                            <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                        @enderror
                        <div class="mt-2 flex gap-2">
                            <x-filament::button wire:click="saveEditNote" size="sm" icon="heroicon-m-check">
                                {{ __('admin.pilot.pipeline.modals.save') }}
                            </x-filament::button>
                            <x-filament::button wire:click="cancelEditNote" size="sm" color="gray" outlined>
                                {{ __('admin.pilot.pipeline.modals.cancel') }}
                            </x-filament::button>
                        </div>
                    @else
                        <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $note->content }}</p>
                        <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                                @if($note->user)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-m-user class="h-3 w-3" />
                                        {{ $note->user->name }}
                                    </span>
                                @endif
                                <span>{{ $note->created_at->format('d M Y, H:i') }}</span>
                                @if($note->created_at->ne($note->updated_at))
                                    <span>{{ __('admin.pilot.pipeline.modals.notes.edited') }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1">
                                <x-filament::icon-button
                                    wire:click="togglePinNote({{ $note->id }})"
                                    :label="$note->is_pinned ? __('admin.pilot.pipeline.modals.notes.unpin') : __('admin.pilot.pipeline.modals.notes.pin')"
                                    icon="heroicon-m-star"
                                    :color="$note->is_pinned ? 'warning' : 'gray'"
                                    size="sm"
                                />
                                <x-filament::icon-button wire:click="startEditNote({{ $note->id }})"
                                    :label="__('admin.pilot.pipeline.modals.notes.edit')" icon="heroicon-m-pencil" color="gray" size="sm" />
                                <x-filament::icon-button wire:click="deleteNote({{ $note->id }})"
                                    wire:confirm="{{ __('admin.pilot.pipeline.modals.notes.delete_confirmation') }}"
                                    :label="__('admin.pilot.pipeline.modals.notes.delete')" icon="heroicon-m-trash" color="danger" size="sm" />
                            </div>
                        </div>
                    @endif
                </article>
            @empty
                <div class="py-10 text-center">
                    <x-heroicon-o-document-text class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
                    <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pilot.pipeline.modals.notes.empty') }}</p>
                </div>
            @endforelse

            <div class="border-t border-gray-200 pt-4 dark:border-gray-700">
                <label for="pipeline-new-note" class="mb-1.5 block text-sm font-medium text-gray-700 dark:text-gray-300">
                    {{ __('admin.pilot.pipeline.modals.notes.new_note') }}
                </label>
                <textarea id="pipeline-new-note" wire:model="newNoteText" rows="3"
                          placeholder="{{ __('admin.pilot.pipeline.modals.notes.placeholder') }}"
                          class="w-full resize-y rounded-xl border border-gray-300 bg-white px-3 py-2.5 text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-800 dark:text-white"></textarea>
                @error('newNoteText')
                    <p class="mt-1 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <x-slot name="footer">
            <div class="flex w-full items-center justify-between gap-3">
                <x-filament::button color="gray" outlined x-on:click="$dispatch('close-modal', { id: 'pipeline-notes' })">
                    {{ __('admin.pilot.pipeline.modals.close') }}
                </x-filament::button>
                <x-filament::button wire:click="addNote" icon="heroicon-m-plus">
                    {{ __('admin.pilot.pipeline.modals.notes.add') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>
    <x-filament::modal
        id="pipeline-history"
        width="xl"
        close-button
        sticky-header
        sticky-footer
        icon="heroicon-o-clock"
        icon-color="gray"
        :heading="__('admin.pilot.pipeline.modals.history.heading')"
        :description="$historyLeadTitle"
        x-effect="if ($wire.showHistoryModal && ! isOpen) open(); if (! $wire.showHistoryModal && isOpen) close()"
        x-on:modal-closed="$wire.set('showHistoryModal', false)"
    >
        @if($historyActivities->isEmpty())
            <div class="py-12 text-center">
                <x-heroicon-o-clock class="mx-auto mb-3 h-10 w-10 text-gray-300 dark:text-gray-600" />
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('admin.pilot.pipeline.modals.history.empty') }}</p>
            </div>
        @else
            <ol class="relative ml-3 space-y-0 border-l border-gray-200 dark:border-gray-700">
                @foreach($historyActivities as $activity)
                    <li class="mb-6 ml-5">
                        <span class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-white dark:ring-gray-900 {{ $activity->bg }}">
                            <x-dynamic-component :component="$activity->icon" class="h-3 w-3 {{ $activity->color }}" />
                        </span>

                        <article class="rounded-xl border border-gray-100 bg-gray-50 px-4 py-3 dark:border-gray-700/60 dark:bg-gray-800/50">
                            <p class="text-sm font-medium leading-snug text-gray-800 dark:text-gray-100">{{ $activity->description }}</p>

                            @if($activity->metadata)
                                <div class="mt-2 flex flex-wrap gap-1.5">
                                    @foreach($activity->metadata as $key => $val)
                                        @if($val)
                                            <span class="inline-flex items-center gap-1 rounded-md border border-gray-200 bg-white px-2 py-0.5 text-xs text-gray-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400">
                                                <span class="font-medium text-gray-400 dark:text-gray-500">{{ str_replace('_', ' ', $key) }}:</span>
                                                {{ is_array($val) ? implode(', ', $val) : $val }}
                                            </span>
                                        @endif
                                    @endforeach
                                </div>
                            @endif

                            <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-gray-400 dark:text-gray-500">
                                @if($activity->user)
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-m-user class="h-3 w-3" />
                                        {{ $activity->user->name }}
                                    </span>
                                @else
                                    <span class="flex items-center gap-1">
                                        <x-heroicon-m-globe-alt class="h-3 w-3" />
                                        {{ __('admin.pilot.pipeline.modals.history.system') }}
                                    </span>
                                @endif
                                <span>{{ $activity->created_at->format('d M Y, H:i') }}</span>
                                <span class="ml-auto">{{ $activity->created_at->diffForHumans() }}</span>
                            </div>
                        </article>
                    </li>
                @endforeach
            </ol>
        @endif

        <x-slot name="footer">
            <div class="flex w-full justify-end">
                <x-filament::button color="gray" outlined x-on:click="$dispatch('close-modal', { id: 'pipeline-history' })">
                    {{ __('admin.pilot.pipeline.modals.close') }}
                </x-filament::button>
            </div>
        </x-slot>
    </x-filament::modal>

</x-filament-panels::page>

