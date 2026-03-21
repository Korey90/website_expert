<div x-data="{ open: false }" class="relative flex items-center" style="margin-right:0.75rem;padding-right:0.75rem;border-right:1px solid #FFFFFF1F">

    {{-- Trigger button --}}
    <button @click="open = !open"
            type="button"
            title="Pinned Notes"
            class="relative flex w-8 h-8 items-center justify-center rounded-lg transition hover:bg-white/10"
            :class="open && 'bg-white/10'"
            >
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:50px;height:20px">
            <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
        </svg>
        @if($pinnedNotes->isNotEmpty())
            <span class="absolute -top-1 left-4 flex h-8 w-8 items-center justify-center rounded-full text-[9px] font-bold leading-none text-white" style="background:rgb(239 68 68)">
                {{ $pinnedNotes->count() > 9 ? '9+' : $pinnedNotes->count() }}
            </span>
        @endif
    </button>

    {{-- Dropdown panel --}}
    <div x-show="open"
         x-cloak
         @click.outside="open = false"
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-1 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-1 scale-95"
         class="absolute right-0 top-full z-50 mt-2 origin-top-right overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
         style="width:320px">

        {{-- Header --}}
        <div class="flex items-center gap-2 border-b border-gray-100 px-4 py-2.5 dark:border-gray-700">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 text-amber-500">
                <path fill-rule="evenodd" d="M6.32 2.577a49.255 49.255 0 0 1 11.36 0c1.497.174 2.57 1.46 2.57 2.93V21a.75.75 0 0 1-1.085.67L12 18.089l-7.165 3.583A.75.75 0 0 1 3.75 21V5.507c0-1.47 1.073-2.756 2.57-2.93Z" clip-rule="evenodd" />
            </svg>
            <p class="text-sm font-semibold text-gray-800 dark:text-white">Pinned Notes</p>
            @if($pinnedNotes->isNotEmpty())
                <span class="ml-auto rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600 dark:bg-gray-700 dark:text-gray-300">
                    {{ $pinnedNotes->count() }}
                </span>
            @endif
        </div>

        {{-- List --}}
        <div class="max-h-96 overflow-y-auto divide-y divide-gray-100 dark:divide-gray-700/50">
            @forelse($pinnedNotes as $note)
                @if($note->lead)
                    <div class="group flex items-start gap-3 px-4 py-3 transition hover:bg-blue-300 dark:hover:bg-white/5">

                        {{-- Amber dot --}}
                        <div class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-amber-400"></div>

                        {{-- Note content (link to lead) --}}
                        <a href="{{ route('filament.admin.resources.leads.view', $note->lead) }}"
                           @click="open = false"
                           class="min-w-0 flex-1">
                            <p class="truncate text-xs font-semibold text-gray-800 transition group-hover:text-primary-600 dark:text-gray-200 dark:group-hover:text-primary-400">
                                {{ $note->lead->title }}
                            </p>
                            <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-gray-500 dark:text-gray-400">
                                {{ \Illuminate\Support\Str::limit(strip_tags($note->content), 90) }}
                            </p>
                            <p class="mt-1 text-[10px] text-gray-400 dark:text-gray-500">
                                @if($note->user){{ $note->user->name }} &middot; @endif{{ $note->created_at->diffForHumans() }}
                            </p>
                        </a>

                        {{-- Unpin button --}}
                        <form method="POST" action="{{ route('lead-notes.unpin', $note) }}" class="shrink-0 self-start">
                            @csrf
                            @method('PATCH')
                            <button type="submit"
                                    title="Unpin"
                                    class="mt-0.5 flex h-6 w-6 items-center justify-center rounded-md text-gray-300 opacity-0 transition hover:bg-red-50 hover:text-red-500 group-hover:opacity-100 dark:text-gray-600 dark:hover:bg-red-500/10 dark:hover:text-red-400">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5">
                                    <path d="M6.28 5.22a.75.75 0 0 0-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 1 0 1.06 1.06L10 11.06l3.72 3.72a.75.75 0 1 0 1.06-1.06L11.06 10l3.72-3.72a.75.75 0 0 0-1.06-1.06L10 8.94 6.28 5.22Z" />
                                </svg>
                            </button>
                        </form>

                    </div>
                @endif
            @empty
                <div class="flex flex-col items-center justify-center gap-2 px-4 py-10 text-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-6 w-6 text-gray-400">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.593 3.322c1.1.128 1.907 1.077 1.907 2.185V21L12 17.25 4.5 21V5.507c0-1.108.806-2.057 1.907-2.185a48.507 48.507 0 0 1 11.186 0Z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">No pinned notes</p>
                    <p class="text-xs text-gray-400 dark:text-gray-500">Pin notes from any lead detail page</p>
                </div>
            @endforelse
        </div>

    </div>

</div>