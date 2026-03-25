<x-filament-panels::page>
    @php
        $statusConfig = match ($record->status) {
            'prospect' => ['label' => 'Prospect', 'bg' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',       'icon' => 'heroicon-m-eye'],
            'active'   => ['label' => 'Active',   'bg' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400', 'icon' => 'heroicon-m-check-circle'],
            'inactive' => ['label' => 'Inactive', 'bg' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400', 'icon' => 'heroicon-m-pause-circle'],
            'archived' => ['label' => 'Archived', 'bg' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',         'icon' => 'heroicon-m-archive-box'],
            default    => ['label' => ucfirst($record->status ?? ''), 'bg' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300', 'icon' => 'heroicon-m-question-mark-circle'],
        };

        $sourceLabel = match ($record->source ?? '') {
            'website'       => 'Website',
            'referral'      => 'Referral',
            'cold_outreach' => 'Cold Outreach',
            'social_media'  => 'Social Media',
            'google_ads'    => 'Google Ads',
            'other'         => 'Other',
            default         => ucwords(str_replace('_', ' ', $record->source ?? '')),
        };

        $currencySymbol = match ($record->currency ?? 'GBP') {
            'GBP' => '£', 'EUR' => '€', 'USD' => '$',
            default => $record->currency ?? '',
        };

        $hasAddress = collect([
            $record->address_line1, $record->address_line2,
            $record->city, $record->county, $record->postcode, $record->country,
        ])->filter()->isNotEmpty();

        $contacts  = $record->contacts()->orderByDesc('is_primary')->get();
        $leadsCount    = $record->leads()->count();
        $quotesCount   = $record->quotes()->count();
        $projectsCount = $record->projects()->count();
        $invoicesCount = $record->invoices()->count();
    @endphp

    {{-- ── Soft-delete warning ────────────────────────────────────────────── --}}
    @if($record->deleted_at)
        <div class="mb-2 flex items-center gap-3 rounded-xl border border-red-200 bg-red-50 px-5 py-3 dark:border-red-800/40 dark:bg-red-900/20">
            <x-heroicon-m-trash class="h-5 w-5 flex-shrink-0 text-red-500" />
            <p class="text-sm font-medium text-red-700 dark:text-red-400">
                This client has been deleted on <strong>{{ $record->deleted_at->format('d M Y, H:i') }}</strong>.
                Use the Restore button to recover it.
            </p>
        </div>
    @endif

    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        {{-- ════════════════════════════════════════════════════════
             LEFT COLUMN  ·  Company · Address · Contacts · Notes
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6 xl:col-span-2">

            {{-- ── Company Overview ────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">

                {{-- header bar --}}
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 text-sm font-bold text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                            {{ mb_strtoupper(mb_substr($record->company_name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900 dark:text-white">{{ $record->company_name }}</h2>
                            @if($record->trading_name)
                                <p class="text-xs text-gray-400 dark:text-gray-500">Trading as: {{ $record->trading_name }}</p>
                            @endif
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 rounded-full px-3 py-1 text-sm font-semibold {{ $statusConfig['bg'] }}">
                        <x-dynamic-component :component="$statusConfig['icon']" class="h-4 w-4" />
                        {{ $statusConfig['label'] }}
                    </span>
                </div>

                <div class="grid grid-cols-2 gap-x-8 gap-y-5 px-6 py-5 sm:grid-cols-3">

                    {{-- Industry --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Industry</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->industry ?: '—' }}
                        </dd>
                    </div>

                    {{-- Website --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Website</dt>
                        <dd class="text-sm">
                            @if($record->website)
                                <a href="{{ $record->website }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1 text-indigo-600 hover:underline dark:text-indigo-400">
                                    <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5 shrink-0" />
                                    {{ parse_url($record->website, PHP_URL_HOST) ?? $record->website }}
                                </a>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Source --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Source</dt>
                        <dd>
                            @if($record->source)
                                <span class="inline-flex items-center rounded-md bg-blue-50 px-2.5 py-1 text-xs font-medium text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">
                                    {{ $sourceLabel }}
                                </span>
                            @else
                                <span class="text-sm text-gray-400">—</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Companies House --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Companies House No.</dt>
                        <dd class="font-mono text-sm text-gray-900 dark:text-white">
                            {{ $record->companies_house_number ?: '—' }}
                        </dd>
                    </div>

                    {{-- VAT --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">VAT Number</dt>
                        <dd class="font-mono text-sm text-gray-900 dark:text-white">
                            {{ $record->vat_number ?: '—' }}
                        </dd>
                    </div>

                    {{-- Created --}}
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Client Since</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">
                            {{ $record->created_at->format('d M Y') }}
                            <span class="ml-1 text-xs text-gray-400">{{ $record->created_at->diffForHumans() }}</span>
                        </dd>
                    </div>

                </div>
            </div>

            {{-- ── Address ──────────────────────────────────────────── --}}
            @if($hasAddress)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-map-pin class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Address</h2>
                </div>
                <div class="px-6 py-5">
                    <address class="not-italic">
                        @foreach(array_filter([$record->address_line1, $record->address_line2, $record->city, $record->county, $record->postcode, $record->country]) as $line)
                            <p class="text-sm text-gray-700 dark:text-gray-300">{{ $line }}</p>
                        @endforeach
                    </address>
                    @php
                        $mapsQuery = urlencode($record->full_address);
                    @endphp
                    @if($mapsQuery)
                        <a href="https://maps.google.com/?q={{ $mapsQuery }}" target="_blank" rel="noopener noreferrer"
                           class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700 dark:text-indigo-400 dark:hover:text-indigo-300">
                            <x-heroicon-m-arrow-top-right-on-square class="h-3.5 w-3.5" />
                            View on Google Maps
                        </a>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Contacts ─────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-users class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Contacts</h2>
                    @if($contacts->count())
                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-500 dark:bg-gray-800">{{ $contacts->count() }}</span>
                    @endif
                </div>

                @if($contacts->count())
                    <div class="divide-y divide-gray-100 dark:divide-gray-800">
                        @foreach($contacts as $contact)
                            <div class="flex items-start gap-4 px-6 py-4">
                                {{-- Avatar --}}
                                <div class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-indigo-50 text-xs font-bold text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400">
                                    {{ mb_strtoupper(mb_substr($contact->first_name ?? '?', 0, 1)) }}
                                </div>
                                {{-- Info --}}
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <p class="font-semibold text-gray-900 dark:text-white">{{ $contact->full_name }}</p>
                                        @if($contact->is_primary)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-400">
                                                <x-heroicon-m-star class="h-3 w-3" />
                                                Primary
                                            </span>
                                        @endif
                                        @if($contact->deleted_at)
                                            <span class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400"
                                                  title="Deleted on {{ $contact->deleted_at->format('d M Y') }}">
                                                <x-heroicon-m-trash class="h-3 w-3" />
                                                Deleted
                                            </span>
                                        @endif
                                    </div>
                                    @if($contact->position)
                                        <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">{{ $contact->position }}</p>
                                    @endif
                                    <div class="mt-1.5 flex flex-wrap gap-x-4 gap-y-1">
                                        @if($contact->email)
                                            <a href="mailto:{{ $contact->email }}"
                                               class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400">
                                                <x-heroicon-m-envelope class="h-3.5 w-3.5" />
                                                {{ $contact->email }}
                                            </a>
                                        @endif
                                        @if($contact->phone)
                                            <a href="tel:{{ $contact->phone }}"
                                               class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400">
                                                <x-heroicon-m-phone class="h-3.5 w-3.5" />
                                                {{ $contact->phone }}
                                            </a>
                                        @endif
                                        @if($contact->mobile)
                                            <a href="tel:{{ $contact->mobile }}"
                                               class="inline-flex items-center gap-1 text-xs text-gray-500 hover:text-indigo-600 dark:text-gray-400 dark:hover:text-indigo-400">
                                                <x-heroicon-m-device-phone-mobile class="h-3.5 w-3.5" />
                                                {{ $contact->mobile }}
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="py-10 text-center">
                        <x-heroicon-o-users class="mx-auto mb-2 h-8 w-8 text-gray-300 dark:text-gray-600" />
                        <p class="text-sm text-gray-400">No contacts added yet.</p>
                    </div>
                @endif
            </div>

            {{-- ── Notes ──────────────────────────────────────────────── --}}
            @if($record->notes)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-document-text class="h-4 w-4 text-yellow-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Notes</h2>
                </div>
                <div class="px-6 py-5">
                    <p class="whitespace-pre-wrap text-sm leading-relaxed text-gray-700 dark:text-gray-300">{{ $record->notes }}</p>
                </div>
            </div>
            @endif

        </div>

        {{-- ════════════════════════════════════════════════════════
             RIGHT COLUMN  ·  Stats · Primary Contact · Assignment · Portal · Meta
        ════════════════════════════════════════════════════════ --}}
        <div class="space-y-6">

            {{-- ── Lifetime Value + Stats ─────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-chart-bar class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Overview</h2>
                </div>
                <div class="px-6 py-5">

                    {{-- LTV --}}
                    <div class="mb-5">
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Lifetime Value</dt>
                        <dd class="text-2xl font-bold text-gray-900 dark:text-white">
                            @if($record->lifetime_value)
                                {{ $currencySymbol }}{{ number_format((float) $record->lifetime_value, 2) }}
                            @else
                                <span class="text-base font-normal text-gray-400">Not set</span>
                            @endif
                        </dd>
                    </div>

                    {{-- Stat grid --}}
                    <div class="grid grid-cols-2 gap-3">
                        <a href="{{ route('filament.admin.resources.leads.index') }}"
                           class="group flex flex-col items-center rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/20">
                            <x-heroicon-o-funnel class="mb-1 h-5 w-5 text-yellow-500" />
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $leadsCount }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Leads</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.quotes.index') }}"
                           class="group flex flex-col items-center rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/20">
                            <x-heroicon-o-clipboard-document-list class="mb-1 h-5 w-5 text-blue-500" />
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $quotesCount }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Quotes</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.projects.index') }}"
                           class="group flex flex-col items-center rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/20">
                            <x-heroicon-o-briefcase class="mb-1 h-5 w-5 text-green-500" />
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $projectsCount }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Projects</span>
                        </a>
                        <a href="{{ route('filament.admin.resources.invoices.index') }}"
                           class="group flex flex-col items-center rounded-lg border border-gray-100 bg-gray-50 px-3 py-3 transition hover:border-indigo-200 hover:bg-indigo-50 dark:border-gray-800 dark:bg-gray-800/40 dark:hover:border-indigo-700 dark:hover:bg-indigo-900/20">
                            <x-heroicon-o-banknotes class="mb-1 h-5 w-5 text-purple-500" />
                            <span class="text-lg font-bold text-gray-900 dark:text-white">{{ $invoicesCount }}</span>
                            <span class="text-xs text-gray-500 dark:text-gray-400">Invoices</span>
                        </a>
                    </div>

                </div>
            </div>

            {{-- ── Primary Contact ─────────────────────────────────── --}}
            @if($record->primary_contact_name || $record->primary_contact_email || $record->primary_contact_phone)
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-user-circle class="h-4 w-4 text-indigo-500" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Primary Contact</h2>
                </div>
                <div class="space-y-3 px-6 py-5">
                    @if($record->primary_contact_name)
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-user class="h-4 w-4 flex-shrink-0 text-gray-400" />
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $record->primary_contact_name }}</span>
                        </div>
                    @endif
                    @if($record->primary_contact_email)
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-envelope class="h-4 w-4 flex-shrink-0 text-gray-400" />
                            <a href="mailto:{{ $record->primary_contact_email }}"
                               class="truncate text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $record->primary_contact_email }}
                            </a>
                        </div>
                    @endif
                    @if($record->primary_contact_phone)
                        <div class="flex items-center gap-2">
                            <x-heroicon-m-phone class="h-4 w-4 flex-shrink-0 text-gray-400" />
                            <a href="tel:{{ $record->primary_contact_phone }}"
                               class="text-sm text-indigo-600 hover:underline dark:text-indigo-400">
                                {{ $record->primary_contact_phone }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif

            {{-- ── Assignment & Currency ───────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-cog-6-tooth class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Assignment</h2>
                </div>
                <div class="space-y-4 px-6 py-5">

                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Account Manager</dt>
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

                    @if($record->currency)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Default Currency</dt>
                        <dd>
                            <span class="inline-flex items-center rounded-md bg-gray-100 px-2.5 py-1 text-sm font-medium text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                                {{ $record->currency }}
                            </span>
                        </dd>
                    </div>
                    @endif

                </div>
            </div>

            {{-- ── Portal Access ────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-lock-closed class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Client Portal</h2>
                </div>
                <div class="px-6 py-5">
                    @if($record->portal_user_id && $record->portalUser)
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30">
                                <x-heroicon-m-check-circle class="h-5 w-5 text-green-600 dark:text-green-400" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-green-700 dark:text-green-400">Account Active</p>
                                <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ $record->portalUser->email }}</p>
                            </div>
                        </div>
                        @if(isset($record->portalUser->last_login_at))
                            <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
                                Last login: {{ $record->portalUser->last_login_at ? $record->portalUser->last_login_at->format('d M Y, H:i') : 'Never' }}
                            </p>
                        @endif
                    @else
                        <div class="flex items-center gap-3">
                            <span class="flex h-9 w-9 flex-shrink-0 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                                <x-heroicon-m-x-circle class="h-5 w-5 text-gray-400" />
                            </span>
                            <div>
                                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">No Portal Account</p>
                                <p class="text-xs text-gray-400">Use the button above to create access.</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ── Metadata ────────────────────────────────────────── --}}
            <div class="overflow-hidden rounded-xl border border-gray-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                <div class="flex items-center gap-2 border-b border-gray-100 px-6 py-4 dark:border-gray-800">
                    <x-heroicon-m-information-circle class="h-4 w-4 text-gray-400" />
                    <h2 class="text-sm font-semibold uppercase tracking-wider text-gray-500 dark:text-gray-400">Details</h2>
                </div>
                <div class="space-y-4 px-6 py-5">
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Client Since</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->created_at->format('d M Y') }}</dd>
                    </div>
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Last Updated</dt>
                        <dd class="text-sm text-gray-900 dark:text-white">{{ $record->updated_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @if($record->deleted_at)
                    <div>
                        <dt class="mb-1 text-xs text-gray-400 dark:text-gray-500">Deleted</dt>
                        <dd class="text-sm font-medium text-red-600 dark:text-red-400">{{ $record->deleted_at->format('d M Y, H:i') }}</dd>
                    </div>
                    @endif
                </div>
            </div>

        </div>

    </div>
</x-filament-panels::page>
