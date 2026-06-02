import { useState } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function DomainsHeroSection({ data }) {
    const [query, setQuery] = useState('');
    const locale = usePage().props.locale ?? 'en';
    const ex = data.extra ?? {};

    const placeholder = ex[`search_placeholder_${locale}`] ?? ex.search_placeholder_en ?? 'yourname…';
    const btnLabel    = ex[`search_button_${locale}`]      ?? ex.search_button_en      ?? 'Check Availability';
    const tlds        = Array.isArray(ex.popular_tlds) ? ex.popular_tlds : [];

    function handleSubmit(e) {
        e.preventDefault();
        if (!query.trim()) return;
        router.get(route('domains.check'), { q: query.trim() });
    }

    return (
        <section id="domain_search" className="py-20 md:py-28 bg-gradient-to-br from-neutral-50 via-white to-brand-50/40 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950">
            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                {data.title && (
                    <h2 className="font-display text-3xl sm:text-4xl lg:text-5xl font-extrabold tracking-tight text-neutral-900 dark:text-white">
                        {data.title}
                    </h2>
                )}
                {data.subtitle && (
                    <p className="mt-4 text-lg text-neutral-600 dark:text-neutral-400 leading-relaxed">
                        {data.subtitle}
                    </p>
                )}

                <form onSubmit={handleSubmit} className="mt-10 flex flex-col sm:flex-row gap-3">
                    <input
                        type="text"
                        value={query}
                        onChange={e => setQuery(e.target.value)}
                        placeholder={placeholder}
                        className="flex-1 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-3.5 text-base text-neutral-900 dark:text-white placeholder:text-neutral-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition"
                    />
                    <button
                        type="submit"
                        className="rounded-xl bg-brand-500 px-7 py-3.5 text-base font-semibold text-white hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20 whitespace-nowrap inline-flex items-center gap-2 justify-center"
                    >
                        {btnLabel}
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </button>
                </form>

                {tlds.length > 0 && (
                    <div className="mt-5 flex flex-wrap justify-center gap-2">
                        {tlds.map(tld => (
                            <button
                                key={tld}
                                type="button"
                                onClick={() => router.get(route('domains.check'), { q: tld })}
                                className="px-3 py-1 rounded-full border border-neutral-200 dark:border-neutral-700 text-xs font-medium text-neutral-600 dark:text-neutral-400 hover:border-brand-500 hover:text-brand-500 transition-colors"
                            >
                                {tld}
                            </button>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
