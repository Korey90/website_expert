@php
    $totalCount = count($tlds);
@endphp

@if($totalCount === 0)
    <div class="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-4">
        <p class="text-sm font-medium text-green-800 dark:text-green-300">
            ✓ All available TLDs from Openprovider are already in your price list.
        </p>
    </div>
@else
<div
    x-data="{
        search: '',
        allTlds: {{ Illuminate\Support\Js::from($tlds) }},
        get filtered() {
            const q = this.search.toLowerCase().replace(/^\./, '');
            return q === '' ? this.allTlds : this.allTlds.filter(t => t.includes(q));
        },
        get selected() {
            return ($wire.mountedActions[0]?.data?.selected_tlds ?? []);
        },
        isChecked(tld) {
            return this.selected.includes(tld);
        },
        toggle(tld) {
            let s = [...this.selected];
            s.includes(tld) ? (s = s.filter(x => x !== tld)) : s.push(tld);
            $wire.set('mountedActions.0.data.selected_tlds', s);
        },
        selectAllFiltered() {
            const merged = [...new Set([...this.selected, ...this.filtered])];
            $wire.set('mountedActions.0.data.selected_tlds', merged);
        },
        deselectAllFiltered() {
            const filtered = this.filtered;
            $wire.set('mountedActions.0.data.selected_tlds', this.selected.filter(t => !filtered.includes(t)));
        },
        get selectedCount() { return this.selected.length; },
    }"
>
    {{-- Info banner --}}
    <div class="mb-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 px-4 py-3 text-sm text-blue-800 dark:text-blue-300">
        <strong>{{ $totalCount }}</strong> TLD{{ $totalCount !== 1 ? 's' : '' }} available from Openprovider not yet in your price list.
        New records will be created as <em>inactive</em> with the default margin.
        Prices are fetched from Openprovider when you click <em>Import selected</em>.
    </div>

    {{-- Search + controls --}}
    <div class="mb-3 flex items-center gap-3 flex-wrap">
        <div class="relative flex-1 min-w-48">
            <span class="absolute inset-y-0 left-2.5 flex items-center text-gray-400 pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="w-4 h-4">
                    <path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" />
                </svg>
            </span>
            <input
                type="text"
                x-model="search"
                placeholder="Filter TLDs…"
                class="block w-full rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 pl-8 pr-3 py-1.5 text-sm text-gray-900 dark:text-gray-100 placeholder-gray-400 focus:outline-none focus:ring-1 focus:ring-primary-500 focus:border-primary-500"
            />
        </div>
        <div class="flex items-center gap-3 text-xs text-gray-600 dark:text-gray-400 shrink-0">
            <span class="tabular-nums font-medium">
                <span x-text="selectedCount"></span> selected
            </span>
            <button type="button" @click="selectAllFiltered()"
                class="text-primary-600 dark:text-primary-400 underline hover:no-underline">
                Select visible
            </button>
            <button type="button" @click="deselectAllFiltered()"
                class="text-primary-600 dark:text-primary-400 underline hover:no-underline">
                Deselect visible
            </button>
        </div>
    </div>

    {{-- TLD grid --}}
    <div class="overflow-y-auto max-h-72 rounded-md border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-3">
        <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-x-3 gap-y-1.5">
            <template x-for="tld in filtered" :key="tld">
                <label
                    class="flex items-center gap-1.5 cursor-pointer select-none group"
                    :class="isChecked(tld) ? 'text-primary-700 dark:text-primary-400 font-medium' : 'text-gray-700 dark:text-gray-300'"
                >
                    <input
                        type="checkbox"
                        :checked="isChecked(tld)"
                        @change="toggle(tld)"
                        class="rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500 shrink-0"
                    />
                    <span class="text-xs truncate" x-text="'.' + tld"></span>
                </label>
            </template>
            <template x-if="filtered.length === 0">
                <p class="col-span-full text-sm text-gray-500 dark:text-gray-400 py-2">No TLDs match your search.</p>
            </template>
        </div>
    </div>

    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
        Showing <span x-text="filtered.length"></span> of {{ $totalCount }} available TLDs.
    </p>
</div>
@endif
