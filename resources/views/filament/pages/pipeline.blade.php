<x-filament-panels::page>
    <div class="space-y-4">
        {{-- Summary bar --}}
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            @foreach($stages as $stage)
                @php
                    $t = $totals[$stage->id] ?? ['count' => 0, 'total' => 0];
                @endphp
                <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400 truncate">{{ $stage->name }}</p>
                    <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $t['count'] }}</p>
                    <p class="text-xs text-gray-500 dark:text-gray-400">£{{ number_format($t['total'] ?? 0, 0) }}</p>
                </div>
            @endforeach
        </div>

        {{-- Kanban board --}}
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
                            <a href="{{ route('filament.admin.resources.leads.edit', $lead) }}"
                               class="block rounded-xl border border-gray-200 bg-white p-3 shadow-sm transition hover:border-primary-400 hover:shadow-md dark:border-gray-700 dark:bg-gray-900">
                                <p class="text-sm font-semibold text-gray-900 dark:text-white leading-tight">{{ $lead->title }}</p>

                                @if($lead->client)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        <x-heroicon-m-building-office class="inline h-3 w-3" />
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
                                    <p class="mt-1.5 text-xs {{ $lead->expected_close_date < now() ? 'text-red-500 font-semibold' : 'text-gray-400' }}">
                                        <x-heroicon-m-clock class="inline h-3 w-3" />
                                        {{ $lead->expected_close_date->format('d M Y') }}
                                    </p>
                                @endif

                                @if($lead->assignedTo)
                                    <p class="mt-1 text-xs text-gray-400 dark:text-gray-500">
                                        <x-heroicon-m-user class="inline h-3 w-3" />
                                        {{ $lead->assignedTo->name }}
                                    </p>
                                @endif
                            </a>
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
</x-filament-panels::page>
