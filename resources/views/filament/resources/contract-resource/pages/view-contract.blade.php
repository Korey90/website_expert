<x-filament-panels::page>
    @php
        $currencySymbol = match ($record->currency ?? 'GBP') {
            'GBP'   => '£',
            'EUR'   => '€',
            'USD'   => '$',
            'PLN'   => 'zł',
            default => $record->currency ?? '',
        };

        $statusConfig = match ($record->status) {
            'draft'     => ['label' => 'Draft',     'bg' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',           'icon' => 'heroicon-m-pencil-square'],
            'sent'      => ['label' => 'Sent',      'bg' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',        'icon' => 'heroicon-m-paper-airplane'],
            'signed'    => ['label' => 'Signed',    'bg' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',    'icon' => 'heroicon-m-check-badge'],
            'expired'   => ['label' => 'Expired',   'bg' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400','icon' => 'heroicon-m-clock'],
            'cancelled' => ['label' => 'Cancelled', 'bg' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',           'icon' => 'heroicon-m-x-circle'],
            default     => ['label' => ucfirst($record->status ?? ''), 'bg' => 'bg-gray-100 text-gray-700', 'icon' => 'heroicon-m-question-mark-circle'],
        };

        $isExpired = $record->expires_at
            && $record->expires_at->isPast()
            && ! in_array($record->status, ['signed', 'expired', 'cancelled']);

        $fmt = fn ($amount) => $currencySymbol . number_format((float) ($amount ?? 0), 2);
    @endphp

    {{-- ── Soft-delete warning ────────────────────────────────────────────── --}}
    @if($record->deleted_at)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 dark:border-red-800/40 dark:bg-red-900/20">
            <x-heroicon-m-trash class="h-5 w-5 flex-shrink-0 text-red-500" />
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                This contract has been deleted on <strong>{{ $record->deleted_at->format('d M Y, H:i') }}</strong>.
                Use the Restore button to recover it.
            </p>
        </div>
    @endif

    {{-- ── Expiring soon / expired warning ──────────────────────────────── --}}
    @if($isExpired)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-yellow-200 bg-yellow-50 px-5 py-3 dark:border-yellow-800/40 dark:bg-yellow-900/20">
            <x-heroicon-m-exclamation-triangle class="h-5 w-5 flex-shrink-0 text-yellow-500" />
            <p class="text-sm font-medium text-yellow-700 dark:text-yellow-400">
                Contract expired on <strong>{{ $record->expires_at->format('d M Y') }}</strong>
                ({{ $record->expires_at->diffForHumans() }}). Consider updating the status.
            </p>
        </div>
    @endif

    {{-- ── Signed banner ───────────────────────────────────────────────── --}}
    @if($record->status === 'signed' && $record->signer_name)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-3 dark:border-green-800/40 dark:bg-green-900/20">
            <x-heroicon-m-check-badge class="h-5 w-5 flex-shrink-0 text-green-600" />
            <p class="text-sm font-medium text-green-700 dark:text-green-400">
                Signed by <strong>{{ $record->signer_name }}</strong>
                on <strong>{{ $record->signed_at?->format('d M Y, H:i') }}</strong>
                @if($record->signer_ip)
                    · IP: <span class="font-mono text-xs">{{ $record->signer_ip }}</span>
                @endif
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ════════════════════════════════════════════════════════
             LEFT COLUMN  ·  Summary · Terms · Signature · Notes
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- ── Contract Summary ─────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <x-heroicon-m-document-check class="h-5 w-5 text-indigo-500" />
                        <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">Contract</span>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->number }}</h2>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $statusConfig['bg'] }}">
                        <x-dynamic-component :component="$statusConfig['icon']" class="h-4 w-4" />
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                {{-- Title --}}
                <div class="border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <p class="text-sm text-gray-400 dark:text-gray-500">Title</p>
                    <p class="mt-0.5 text-base font-semibold text-gray-900 dark:text-white">{{ $record->title }}</p>
                </div>

                {{-- Key fields grid --}}
                <div class="grid grid-cols-2 gap-x-8 gap-y-5 px-6 py-5 sm:grid-cols-4">

                    {{-- Value --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Contract Value</dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white">{{ $fmt($record->value) }}</dd>
                    </div>

                    {{-- Currency --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Currency</dt>
                        <dd class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $record->currency }}
                        </dd>
                    </div>

                    {{-- Start Date --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Starts</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->starts_at ? $record->starts_at->format('d M Y') : '—' }}
                        </dd>
                    </div>

                    {{-- Expiry Date --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Expires</dt>
                        <dd class="text-sm">
                            @if($record->expires_at)
                                <span class="{{ $isExpired ? 'font-semibold text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                                    {{ $record->expires_at->format('d M Y') }}
                                </span>
                                @if($isExpired)
                                    <span class="ml-1 rounded bg-yellow-100 px-1.5 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">expired</span>
                                @endif
                            @else
                                <span class="text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Sent --}}
                    @if($record->sent_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Sent to Portal</dt>
                        <dd class="flex items-center gap-1.5 text-sm text-gray-900 dark:text-white">
                            <x-heroicon-m-paper-airplane class="h-4 w-4 text-blue-400" />
                            {{ $record->sent_at->format('d M Y, H:i') }}
                        </dd>
                    </div>
                    @endif

                    {{-- Signed --}}
                    @if($record->signed_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Signed</dt>
                        <dd class="flex items-center gap-1.5 text-sm font-medium text-green-600 dark:text-green-400">
                            <x-heroicon-m-check-badge class="h-4 w-4" />
                            {{ $record->signed_at->format('d M Y, H:i') }}
                        </dd>
                    </div>
                    @endif

                </div>

                {{-- PDF attachment --}}
                @if($record->file_path)
                    <div class="border-t border-gray-100 px-6 py-4 dark:border-gray-800">
                        <a href="{{ \Illuminate\Support\Facades\Storage::url($record->file_path) }}"
                           target="_blank"
                           rel="noopener noreferrer"
                           class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700">
                            <x-heroicon-m-paper-clip class="h-4 w-4 text-gray-400" />
                            Download Signed Contract PDF
                            <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5 opacity-60" />
                        </a>
                    </div>
                @endif
            </div>

            {{-- ── Terms & Conditions ──────────────────────────────── --}}
            @if($record->terms)
            <div x-data="{ open: false }"
                 class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <button type="button"
                        @click="open = !open"
                        class="flex w-full items-center justify-between gap-2 px-6 py-4 text-left hover:bg-gray-50 dark:hover:bg-gray-800/40">
                    <div class="flex items-center gap-2">
                        <x-heroicon-m-scale class="h-4 w-4 text-indigo-400" />
                        <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Terms &amp; Conditions</h2>
                    </div>
                    <x-heroicon-m-chevron-down class="h-4 w-4 text-gray-400 transition-transform duration-200 dark:text-gray-500"
                                               ::class="open ? 'rotate-180' : ''" />
                </button>
                <div x-show="open"
                     x-collapse
                     class="border-t border-gray-100 dark:border-gray-800">
                    <div class="prose prose-sm dark:prose-invert max-w-none px-6 py-5 text-gray-700 dark:text-gray-300">
                        {!! $record->terms !!}
                    </div>
                </div>
            </div>
            @endif

            {{-- ── Signature ────────────────────────────────────────── --}}
            @if($record->status === 'signed')
            <div class="overflow-hidden rounded-xl border border-green-200 bg-white dark:border-green-800/40 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-green-100 px-6 py-4 dark:border-green-800/30">
                    <x-heroicon-m-check-badge class="h-4 w-4 text-green-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Signature Record</h2>
                </div>
                <div class="grid grid-cols-1 gap-4 px-6 py-5 sm:grid-cols-3">

                    @if($record->signer_name)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Signed By</dt>
                        <dd class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->signer_name }}</dd>
                    </div>
                    @endif

                    @if($record->signed_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Date &amp; Time</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->signed_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif

                    @if($record->signer_ip)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">IP Address</dt>
                        <dd class="font-mono text-sm text-gray-700 dark:text-gray-300">{{ $record->signer_ip }}</dd>
                    </div>
                    @endif

                </div>

                @if($record->signature_data)
                <div class="border-t border-green-100 px-6 py-5 dark:border-green-800/30">
                    <dt class="mb-3 text-xs text-gray-400 dark:text-gray-500">Handwritten Signature</dt>
                    <div class="inline-block rounded-lg border border-gray-200 bg-white p-2">
                        <img src="{{ $record->signature_data }}"
                             alt="Client signature"
                             class="max-h-28 w-auto" />
                    </div>
                </div>
                @else
                    <div class="border-t border-green-100 px-6 py-4 dark:border-green-800/30">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-green-50 px-3 py-1 text-xs font-medium text-green-700 dark:bg-green-900/30 dark:text-green-400">
                            <x-heroicon-m-check-circle class="h-3.5 w-3.5" />
                            Electronic acceptance (checkbox)
                        </span>
                    </div>
                @endif
            </div>
            @endif

            {{-- ── Internal Notes ──────────────────────────────────── --}}
            @if($record->notes)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Internal Notes</h2>
                </div>
                <div class="px-6 py-5">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- ════════════════════════════════════════════════════════
             RIGHT COLUMN  ·  Client · Project · Quote · Metadata
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- ── Client ─────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-building-office-2 class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Client</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->client)
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                {{ mb_strtoupper(mb_substr($record->client->company_name ?? '?', 0, 1)) }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <p class="font-semibold text-gray-900 dark:text-white">{{ $record->client->company_name }}</p>
                                    @if($record->client->deleted_at)
                                        <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                                              title="Deleted on {{ $record->client->deleted_at->format('d M Y') }}">
                                            <x-heroicon-m-trash class="h-3 w-3" />
                                            Deleted
                                        </span>
                                    @endif
                                </div>
                                @if($record->client->primary_contact_name)
                                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_name }}</p>
                                @endif
                                @if($record->client->primary_contact_email)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_email }}</p>
                                @endif
                                @if($record->client->primary_contact_phone)
                                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $record->client->primary_contact_phone }}</p>
                                @endif
                                @if(! $record->client->deleted_at)
                                    <a href="{{ route('filament.admin.resources.clients.view', $record->client_id) }}"
                                       class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                        <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                        View Client
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <p class="text-sm text-gray-400">No client linked.</p>
                    @endif
                </div>
            </div>

            {{-- ── Related Project ──────────────────────────────────── --}}
            @if($record->project_id)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-folder-open class="h-4 w-4 text-blue-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Project</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->project)
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->project->title }}</p>
                        @if($record->project->status)
                            <span class="mt-1.5 inline-block rounded-md bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                                {{ ucfirst(str_replace('_', ' ', $record->project->status)) }}
                            </span>
                        @endif
                        @if(! $record->project->deleted_at)
                            <a href="{{ route('filament.admin.resources.projects.view', $record->project_id) }}"
                               class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                View Project
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Project #{{ $record->project_id }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Related Quote ────────────────────────────────────── --}}
            @if($record->quote_id)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-clipboard-document-list class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Related Quote</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->quote)
                        <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->quote->number }}</p>
                        @if($record->quote->status)
                            <span class="mt-1.5 inline-block rounded-md bg-yellow-50 px-2 py-0.5 text-xs font-medium text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">
                                {{ ucfirst($record->quote->status) }}
                            </span>
                        @endif
                        @if(! $record->quote->deleted_at)
                            <a href="{{ route('filament.admin.resources.quotes.view', $record->quote_id) }}"
                               class="mt-2 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                                <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                                View Quote
                            </a>
                        @endif
                    @else
                        <p class="text-sm text-gray-400">Quote #{{ $record->quote_id }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Contract Template ────────────────────────────────── --}}
            @if($record->contractTemplate)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-rectangle-stack class="h-4 w-4 text-purple-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Template</h2>
                </div>
                <div class="px-6 py-5">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->contractTemplate->name }}</p>
                    @if($record->contractTemplate->type)
                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $record->contractTemplate->type }}</p>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Metadata ────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-information-circle class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Details</h2>
                </div>
                <div class="space-y-4 px-6 py-5">

                    @if($record->createdBy)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Created By</dt>
                        <dd class="flex items-center gap-1.5 text-sm text-gray-900 dark:text-white">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-indigo-100 text-xs font-semibold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                {{ mb_strtoupper(mb_substr($record->createdBy->name, 0, 1)) }}
                            </span>
                            {{ $record->createdBy->name }}
                        </dd>
                    </div>
                    @endif

                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Created</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->created_at->format('d M Y, H:i') }}
                            <span class="ml-1 text-xs text-gray-400">{{ $record->created_at->diffForHumans() }}</span>
                        </dd>
                    </div>

                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Last Updated</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->updated_at->format('d M Y, H:i') }}</dd>
                    </div>

                    @if($record->sent_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Sent to Portal</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->sent_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif

                    @if($record->signed_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Signed</dt>
                        <dd class="text-sm font-medium text-green-600 dark:text-green-400">{{ $record->signed_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif

                    @if($record->starts_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Starts</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->starts_at->format('d M Y') }}</dd>
                    </div>
                    @endif

                    @if($record->expires_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Expires</dt>
                        <dd class="text-sm {{ $isExpired ? 'font-semibold text-yellow-600 dark:text-yellow-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $record->expires_at->format('d M Y') }}
                        </dd>
                    </div>
                    @endif

                </div>
            </div>

        </div>{{-- /right col --}}

    </div>{{-- /grid --}}

</x-filament-panels::page>
