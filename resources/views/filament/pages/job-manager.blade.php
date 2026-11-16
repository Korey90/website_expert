<x-filament-panels::page>
    <div class="space-y-4">

        {{-- ================================================================
             QUEUE STATUS BAR
        ================================================================ --}}
        @php $status = $this->queueStatus(); @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">

            {{-- Worker status --}}
            <div class="rounded-xl border p-4 flex items-center gap-3
                {{ $status['worker_active']
                    ? 'border-green-200 dark:border-green-700 bg-green-50 dark:bg-green-900/20'
                    : 'border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20' }}">
                <span class="relative flex h-3 w-3 shrink-0">
                    @if($status['worker_active'])
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-green-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-green-500"></span>
                    @else
                        <span class="relative inline-flex rounded-full h-3 w-3 bg-red-500"></span>
                    @endif
                </span>
                <div>
                    <p class="text-xs font-semibold {{ $status['worker_active'] ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300' }}">
                        {{ __('jobs.worker') }}
                    </p>
                    <p class="text-xs {{ $status['worker_active'] ? 'text-green-600 dark:text-green-400' : 'text-red-500 dark:text-red-400' }}">
                        {{ $status['worker_active'] ? __('jobs.worker_running') : __('jobs.worker_idle') }}
                    </p>
                </div>
            </div>

            {{-- Pending --}}
            <div class="rounded-xl border border-yellow-200 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-4 flex items-center gap-3">
                <x-heroicon-o-clock class="w-7 h-7 text-yellow-500 shrink-0" />
                <div>
                    <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-300 leading-none">{{ $status['total_pending'] }}</p>
                    <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-0.5">{{ __('jobs.tab_pending') }}</p>
                </div>
            </div>

            {{-- Active --}}
            <div class="rounded-xl border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-4 flex items-center gap-3">
                <x-heroicon-o-bolt class="w-7 h-7 text-blue-500 shrink-0" />
                <div>
                    <p class="text-2xl font-bold text-blue-700 dark:text-blue-300 leading-none">{{ $status['active'] }}</p>
                    <p class="text-xs text-blue-600 dark:text-blue-400 mt-0.5">{{ __('jobs.running') }}</p>
                </div>
            </div>

            {{-- Failed --}}
            <div class="rounded-xl border {{ $status['total_failed'] > 0 ? 'border-red-200 dark:border-red-700 bg-red-50 dark:bg-red-900/20' : 'border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/40' }} p-4 flex items-center gap-3">
                <x-heroicon-o-x-circle class="w-7 h-7 {{ $status['total_failed'] > 0 ? 'text-red-500' : 'text-gray-400' }} shrink-0" />
                <div>
                    <p class="text-2xl font-bold {{ $status['total_failed'] > 0 ? 'text-red-700 dark:text-red-300' : 'text-gray-600 dark:text-gray-300' }} leading-none">{{ $status['total_failed'] }}</p>
                    <p class="text-xs {{ $status['total_failed'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500 dark:text-gray-400' }} mt-0.5">
                        {{ __('jobs.tab_failed') }}
                        @if($status['last_failed_at'])
                            &middot; {{ \Carbon\Carbon::parse($status['last_failed_at'])->diffForHumans() }}
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- Per-queue breakdown (only when there's something to show) --}}
        @if(count($status['by_queue']) > 0)
            <x-filament::section :collapsible="true" :collapsed="true">
                <x-slot name="heading">{{ __('jobs.queues_breakdown') }}</x-slot>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                <th class="py-2 pr-4 font-semibold text-gray-600 dark:text-gray-400">{{ __('jobs.queue') }}</th>
                                <th class="py-2 pr-4 font-semibold text-yellow-600 dark:text-yellow-400">{{ __('jobs.waiting') }}</th>
                                <th class="py-2 pr-4 font-semibold text-blue-600 dark:text-blue-400">{{ __('jobs.running') }}</th>
                                <th class="py-2 font-semibold text-red-600 dark:text-red-400">{{ __('jobs.tab_failed') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @foreach($status['by_queue'] as $qName => $counts)
                                <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                    <td class="py-2 pr-4">
                                        <span class="rounded px-2 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 font-mono">
                                            {{ $qName }}
                                        </span>
                                    </td>
                                    <td class="py-2 pr-4 text-sm font-semibold {{ $counts['pending'] > 0 ? 'text-yellow-600 dark:text-yellow-400' : 'text-gray-400' }}">
                                        {{ $counts['pending'] }}
                                    </td>
                                    <td class="py-2 pr-4 text-sm font-semibold {{ $counts['active'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-400' }}">
                                        {{ $counts['active'] }}
                                    </td>
                                    <td class="py-2 text-sm font-semibold {{ $counts['failed'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-400' }}">
                                        {{ $counts['failed'] }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-filament::section>
        @endif

        {{-- ================================================================
             TABS
        ================================================================ --}}
        <div class="flex gap-1 border-b border-gray-200 dark:border-gray-700">
            @php
                $tabs = [
                    'failed'  => [
                        'label' => __('jobs.tab_failed'),
                        'count' => $this->failedJobsCount(),
                        'color' => 'text-red-600 dark:text-red-400 border-red-500',
                    ],
                    'pending' => [
                        'label' => __('jobs.tab_pending'),
                        'count' => $this->pendingJobsCount(),
                        'color' => 'text-yellow-600 dark:text-yellow-400 border-yellow-500',
                    ],
                    'batches' => [
                        'label' => __('jobs.tab_batches'),
                        'count' => $this->jobBatchesCount(),
                        'color' => 'text-blue-600 dark:text-blue-400 border-blue-500',
                    ],
                ];
            @endphp
            @foreach($tabs as $tabKey => $tab)
                <button
                    wire:click="switchTab('{{ $tabKey }}')"
                    class="px-4 py-2.5 text-sm font-medium border-b-2 transition -mb-px
                        {{ $activeTab === $tabKey
                            ? $tab['color'] . ' ' . str_replace('text-', 'border-', explode(' ', $tab['color'])[0])
                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200' }}"
                >
                    {{ $tab['label'] }}
                    <span class="ml-1.5 rounded-full px-2 py-0.5 text-xs font-semibold
                        {{ $activeTab === $tabKey
                            ? 'bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-200'
                            : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400' }}">
                        {{ $tab['count'] }}
                    </span>
                </button>
            @endforeach
        </div>

        {{-- ================================================================
             FILTERS BAR (shared)
        ================================================================ --}}
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">

                {{-- Queue filter (not on batches tab) --}}
                @if($activeTab !== 'batches')
                    @php
                        $queues = $activeTab === 'failed'
                            ? $this->failedJobQueues()
                            : $this->pendingJobQueues();
                    @endphp
                    <div class="min-w-44">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            {{ __('jobs.queue') }}
                        </label>
                        <select
                            wire:model.live="queueFilter"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                        >
                            <option value="">— {{ __('jobs.all_queues') }} —</option>
                            @foreach($queues as $q)
                                <option value="{{ $q }}">{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                {{-- Search --}}
                <div class="flex-1 min-w-56">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        {{ __('jobs.search') }}
                    </label>
                    <input
                        type="text"
                        wire:model.live.debounce.400ms="search"
                        placeholder="{{ __('jobs.search_placeholder') }}"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 shadow-sm focus:outline-none focus:ring-2 focus:ring-primary-500"
                    />
                </div>

                {{-- Bulk actions (failed tab only) --}}
                @if($activeTab === 'failed')
                    <div class="flex gap-2 ml-auto">
                        <button
                            wire:click="retryAll"
                            wire:confirm="{{ __('jobs.confirm_retry_all') }}"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-green-300 dark:border-green-700 bg-green-50 dark:bg-green-900/20 px-3 py-2 text-sm font-medium text-green-700 dark:text-green-300 hover:bg-green-100 dark:hover:bg-green-900/40 transition"
                        >
                            <x-heroicon-o-arrow-path class="w-4 h-4" />
                            {{ __('jobs.retry_all') }}
                        </button>
                        <button
                            wire:click="confirmFlush"
                            class="inline-flex items-center gap-1.5 rounded-lg border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/20 px-3 py-2 text-sm font-medium text-red-700 dark:text-red-300 hover:bg-red-100 dark:hover:bg-red-900/40 transition"
                        >
                            <x-heroicon-o-trash class="w-4 h-4" />
                            {{ __('jobs.flush_all') }}
                        </button>
                    </div>
                @endif
            </div>
        </x-filament::section>

        {{-- ================================================================
             TAB: FAILED JOBS
        ================================================================ --}}
        @if($activeTab === 'failed')
            @php $failed = $this->failedJobs(); @endphp

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('jobs.tab_failed') }}
                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ number_format($failed->total()) }})
                    </span>
                </x-slot>

                @if($failed->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">
                        {{ __('jobs.no_failed') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-8">#</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('jobs.job_class') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-28">{{ __('jobs.queue') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-40">{{ __('jobs.failed_at') }}</th>
                                    <th class="py-2 font-semibold text-gray-600 dark:text-gray-400 w-44 text-right">{{ __('jobs.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($failed as $job)
                                    <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2 pr-3 text-xs text-gray-400 dark:text-gray-500 font-mono">
                                            {{ $job->id }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            <p class="font-medium text-gray-800 dark:text-gray-100 text-xs font-mono truncate max-w-xs" title="{{ $job->jobClass() }}">
                                                {{ class_basename($job->jobClass()) }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $job->uuid }}</p>
                                        </td>
                                        <td class="py-2 pr-3">
                                            <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                {{ $job->queue }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $job->failed_at?->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    wire:click="viewFailedJobPayload({{ $job->id }})"
                                                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                                    title="{{ __('jobs.view_payload') }}"
                                                >
                                                    <x-heroicon-o-eye class="w-4 h-4" />
                                                </button>
                                                <button
                                                    wire:click="viewFailedJobException({{ $job->id }})"
                                                    class="text-xs text-red-400 hover:text-red-600 transition"
                                                    title="{{ __('jobs.view_exception') }}"
                                                >
                                                    <x-heroicon-o-bug-ant class="w-4 h-4" />
                                                </button>
                                                <button
                                                    wire:click="retryJob('{{ $job->uuid }}')"
                                                    wire:confirm="{{ __('jobs.confirm_retry') }}"
                                                    class="text-xs text-green-500 hover:text-green-700 transition"
                                                    title="{{ __('jobs.retry') }}"
                                                >
                                                    <x-heroicon-o-arrow-path class="w-4 h-4" />
                                                </button>
                                                <button
                                                    wire:click="deleteFailedJob('{{ $job->uuid }}')"
                                                    wire:confirm="{{ __('jobs.confirm_delete') }}"
                                                    class="text-xs text-red-400 hover:text-red-600 transition"
                                                    title="{{ __('jobs.delete') }}"
                                                >
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('filament.pages.partials.job-pagination', ['paginator' => $failed])
                @endif
            </x-filament::section>
        @endif

        {{-- ================================================================
             TAB: PENDING JOBS
        ================================================================ --}}
        @if($activeTab === 'pending')
            @php $pending = $this->pendingJobs(); @endphp

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('jobs.tab_pending') }}
                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ number_format($pending->total()) }})
                    </span>
                </x-slot>

                @if($pending->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">
                        {{ __('jobs.no_pending') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-8">#</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('jobs.job_class') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-28">{{ __('jobs.queue') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-20">{{ __('jobs.attempts') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-16">{{ __('jobs.status') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-40">{{ __('jobs.created_at') }}</th>
                                    <th class="py-2 font-semibold text-gray-600 dark:text-gray-400 w-24 text-right">{{ __('jobs.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($pending as $job)
                                    <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2 pr-3 text-xs text-gray-400 dark:text-gray-500 font-mono">
                                            {{ $job->id }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            <p class="font-medium text-gray-800 dark:text-gray-100 text-xs font-mono truncate max-w-xs" title="{{ $job->jobClass() }}">
                                                {{ class_basename($job->jobClass()) }}
                                            </p>
                                        </td>
                                        <td class="py-2 pr-3">
                                            <span class="rounded px-1.5 py-0.5 text-xs font-medium bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                                                {{ $job->queue }}
                                            </span>
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-gray-500 dark:text-gray-400">
                                            {{ $job->attempts }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            @if($job->isReserved())
                                                <span class="rounded px-1.5 py-0.5 text-xs font-semibold bg-yellow-100 dark:bg-yellow-900/40 text-yellow-700 dark:text-yellow-300">
                                                    {{ __('jobs.running') }}
                                                </span>
                                            @else
                                                <span class="rounded px-1.5 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                                    {{ __('jobs.waiting') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-gray-500 dark:text-gray-400 whitespace-nowrap">
                                            {{ $job->createdAtCarbon()->format('Y-m-d H:i:s') }}
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                <button
                                                    wire:click="viewPendingJobPayload({{ $job->id }})"
                                                    class="text-xs text-gray-500 dark:text-gray-400 hover:text-primary-600 dark:hover:text-primary-400 transition"
                                                    title="{{ __('jobs.view_payload') }}"
                                                >
                                                    <x-heroicon-o-eye class="w-4 h-4" />
                                                </button>
                                                <button
                                                    wire:click="deletePendingJob({{ $job->id }})"
                                                    wire:confirm="{{ __('jobs.confirm_delete') }}"
                                                    class="text-xs text-red-400 hover:text-red-600 transition"
                                                    title="{{ __('jobs.delete') }}"
                                                >
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('filament.pages.partials.job-pagination', ['paginator' => $pending])
                @endif
            </x-filament::section>
        @endif

        {{-- ================================================================
             TAB: JOB BATCHES
        ================================================================ --}}
        @if($activeTab === 'batches')
            @php $batches = $this->jobBatches(); @endphp

            <x-filament::section>
                <x-slot name="heading">
                    {{ __('jobs.tab_batches') }}
                    <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                        ({{ number_format($batches->total()) }})
                    </span>
                </x-slot>

                @if($batches->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400 py-6 text-center">
                        {{ __('jobs.no_batches') }}
                    </p>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-gray-200 dark:border-gray-700 text-left">
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400">{{ __('jobs.batch_name') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-32">{{ __('jobs.progress') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-20">{{ __('jobs.total') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-20">{{ __('jobs.pending') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-20">{{ __('jobs.failed_count') }}</th>
                                    <th class="py-2 pr-3 font-semibold text-gray-600 dark:text-gray-400 w-16">{{ __('jobs.status') }}</th>
                                    <th class="py-2 font-semibold text-gray-600 dark:text-gray-400 w-24 text-right">{{ __('jobs.actions') }}</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                @foreach($batches as $batch)
                                    @php
                                        $progress = $batch->total_jobs > 0
                                            ? round((($batch->total_jobs - $batch->pending_jobs) / $batch->total_jobs) * 100)
                                            : 100;
                                        $isCancelled = $batch->cancelled_at !== null;
                                        $isFinished  = $batch->finished_at !== null;
                                    @endphp
                                    <tr class="align-middle hover:bg-gray-50 dark:hover:bg-gray-800/50">
                                        <td class="py-2 pr-3">
                                            <p class="font-medium text-gray-800 dark:text-gray-100 text-xs truncate max-w-xs" title="{{ $batch->id }}">
                                                {{ $batch->name ?: '—' }}
                                            </p>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 font-mono mt-0.5">{{ $batch->id }}</p>
                                        </td>
                                        <td class="py-2 pr-3">
                                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                                <div
                                                    class="h-2 rounded-full transition-all
                                                        {{ $isCancelled ? 'bg-gray-400' : ($batch->failed_jobs > 0 ? 'bg-red-500' : 'bg-green-500') }}"
                                                    style="width: {{ $progress }}%"
                                                ></div>
                                            </div>
                                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-0.5">{{ $progress }}%</p>
                                        </td>
                                        <td class="py-2 pr-3 text-xs text-gray-500 dark:text-gray-400">{{ $batch->total_jobs }}</td>
                                        <td class="py-2 pr-3 text-xs text-gray-500 dark:text-gray-400">{{ $batch->pending_jobs }}</td>
                                        <td class="py-2 pr-3 text-xs {{ $batch->failed_jobs > 0 ? 'text-red-600 dark:text-red-400 font-semibold' : 'text-gray-500 dark:text-gray-400' }}">
                                            {{ $batch->failed_jobs }}
                                        </td>
                                        <td class="py-2 pr-3">
                                            @if($isCancelled)
                                                <span class="rounded px-1.5 py-0.5 text-xs font-semibold bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400">
                                                    {{ __('jobs.cancelled') }}
                                                </span>
                                            @elseif($isFinished)
                                                <span class="rounded px-1.5 py-0.5 text-xs font-semibold bg-green-100 dark:bg-green-900/40 text-green-700 dark:text-green-300">
                                                    {{ __('jobs.finished') }}
                                                </span>
                                            @else
                                                <span class="rounded px-1.5 py-0.5 text-xs font-semibold bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300">
                                                    {{ __('jobs.running') }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="py-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if(! $isCancelled && ! $isFinished)
                                                    <button
                                                        wire:click="cancelBatch('{{ $batch->id }}')"
                                                        wire:confirm="{{ __('jobs.confirm_cancel_batch') }}"
                                                        class="text-xs text-yellow-500 hover:text-yellow-700 transition"
                                                        title="{{ __('jobs.cancel') }}"
                                                    >
                                                        <x-heroicon-o-stop-circle class="w-4 h-4" />
                                                    </button>
                                                @endif
                                                <button
                                                    wire:click="deleteBatch('{{ $batch->id }}')"
                                                    wire:confirm="{{ __('jobs.confirm_delete') }}"
                                                    class="text-xs text-red-400 hover:text-red-600 transition"
                                                    title="{{ __('jobs.delete') }}"
                                                >
                                                    <x-heroicon-o-trash class="w-4 h-4" />
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @include('filament.pages.partials.job-pagination', ['paginator' => $batches])
                @endif
            </x-filament::section>
        @endif

        {{-- ================================================================
             PAYLOAD / EXCEPTION MODAL
        ================================================================ --}}
        @if($showPayloadModal)
            <div
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60"
                wire:click.self="closeModal"
            >
                <div class="relative w-full max-w-3xl max-h-[80vh] flex flex-col rounded-xl bg-white dark:bg-gray-900 shadow-2xl">

                    {{-- Header --}}
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200 dark:border-gray-700">
                        <h3 class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                            {{ $modalTitle }}
                        </h3>
                        <button wire:click="closeModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200 transition">
                            <x-heroicon-o-x-mark class="w-5 h-5" />
                        </button>
                    </div>

                    {{-- Content --}}
                    <div class="overflow-y-auto p-5 flex-1">
                        <pre class="text-xs text-gray-800 dark:text-gray-200 font-mono whitespace-pre-wrap break-all leading-relaxed">{{ $modalContent }}</pre>
                    </div>

                </div>
            </div>
        @endif

        {{-- ================================================================
             FLUSH CONFIRM MODAL
        ================================================================ --}}
        @if($showConfirmFlush)
            <div class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60">
                <div class="w-full max-w-md rounded-xl bg-white dark:bg-gray-900 shadow-2xl p-6 space-y-4">
                    <h3 class="text-base font-semibold text-gray-800 dark:text-gray-100">
                        {{ __('jobs.flush_confirm_title') }}
                    </h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        {{ __('jobs.flush_confirm_body') }}
                    </p>
                    <div class="flex justify-end gap-3 pt-2">
                        <button
                            wire:click="cancelFlush"
                            class="px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition"
                        >
                            {{ __('jobs.cancel') }}
                        </button>
                        <button
                            wire:click="flushAllFailed"
                            class="px-4 py-2 text-sm rounded-lg bg-red-600 hover:bg-red-700 text-white font-medium transition"
                        >
                            {{ __('jobs.flush_all') }}
                        </button>
                    </div>
                </div>
            </div>
        @endif

    </div>
</x-filament-panels::page>
