<div
    x-data="{
        locale: 'en',
        get src() {
            return '/admin/email-preview/{{ $record->id }}/' + this.locale;
        }
    }"
    class="flex flex-col gap-0"
>
    {{-- Locale switcher --}}
    <div class="flex gap-2 items-center mb-4">
        <span class="text-sm font-medium text-gray-500 mr-1">Language:</span>

        <template x-for="tab in [{ code: 'en', label: '🇬🇧 English' }, { code: 'pl', label: '🇵🇱 Polish' }, { code: 'pt', label: '🇵🇹 Portuguese' }]" :key="tab.code">
            <button
                type="button"
                @click="locale = tab.code"
                :class="locale === tab.code
                    ? 'bg-primary-600 text-white border-primary-600 shadow-sm'
                    : 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'"
                class="px-3 py-1.5 rounded-lg border text-sm font-medium transition-all"
                x-text="tab.label"
            ></button>
        </template>

        <a
            :href="src"
            target="_blank"
            class="ml-auto flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-gray-300 bg-white text-sm text-gray-600 hover:bg-gray-50 transition-all"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
            </svg>
            Open full page
        </a>
    </div>

    {{-- Email preview iframe --}}
    <div class="rounded-xl overflow-hidden border border-gray-200 shadow-inner bg-gray-100">
        <iframe
            :src="src"
            :key="locale"
            class="w-full block"
            style="height: 580px; border: none;"
            title="Email Preview"
        ></iframe>
    </div>

    <p class="mt-2 text-xs text-center text-gray-400">
        Preview uses sample data. Actual values will be filled in when the email is sent.
    </p>
</div>
