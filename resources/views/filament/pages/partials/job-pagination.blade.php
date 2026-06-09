@if($paginator->lastPage() > 1)
    <div class="mt-4 flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
        <span>
            {{ __('jobs.page') }} {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            &nbsp;&middot;&nbsp;
            {{ number_format($paginator->total()) }} {{ __('jobs.records') }}
        </span>
        <div class="flex gap-2">
            <button
                wire:click="previousPage"
                @if($paginator->onFirstPage()) disabled @endif
                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
                &larr; {{ __('jobs.prev') }}
            </button>
            <button
                wire:click="nextPage({{ $paginator->lastPage() }})"
                @if(! $paginator->hasMorePages()) disabled @endif
                class="px-3 py-1 rounded border border-gray-300 dark:border-gray-600 disabled:opacity-40 hover:bg-gray-100 dark:hover:bg-gray-700 transition"
            >
                {{ __('jobs.next') }} &rarr;
            </button>
        </div>
    </div>
@endif
