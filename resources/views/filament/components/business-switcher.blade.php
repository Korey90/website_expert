@auth
    @php
        $businesses = auth()->user()->businesses()
            ->wherePivot('is_active', true)
            ->where('businesses.is_active', true)
            ->orderBy('business_users.id')
            ->get();
        $currentBusiness = currentBusiness();
    @endphp

    @if($businesses->count() > 1 && $currentBusiness)
        <div x-data="{ open: false }" class="relative flex items-center" style="margin-right:0.75rem">
            <button
                type="button"
                @click="open = ! open"
                :aria-expanded="open"
                class="flex max-w-64 items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/10"
                title="Switch workspace"
            >
                <span class="truncate">{{ $currentBusiness->name }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                @click.outside="open = false"
                x-transition
                class="absolute right-0 top-full z-50 mt-2 w-80 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-2xl dark:border-gray-700 dark:bg-gray-900"
            >
                <div class="border-b border-gray-100 px-4 py-3 dark:border-gray-700">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Workspace</p>
                    <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">Finance and HMRC data remain isolated.</p>
                </div>

                <div class="p-1.5">
                    @foreach($businesses as $business)
                        <form method="POST" action="{{ route('business.switch', $business) }}">
                            @csrf
                            <button
                                type="submit"
                                class="flex w-full items-center justify-between gap-3 rounded-lg px-3 py-2.5 text-left text-sm transition hover:bg-gray-100 dark:hover:bg-white/10"
                            >
                                <span class="truncate text-gray-800 dark:text-gray-200">{{ $business->name }}</span>

                                @if($business->is($currentBusiness))
                                    <span class="shrink-0 rounded-full bg-primary-50 px-2 py-0.5 text-xs font-medium text-primary-700 dark:bg-primary-500/10 dark:text-primary-400">Active</span>
                                @endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
@endauth
