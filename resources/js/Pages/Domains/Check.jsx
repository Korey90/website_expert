import { Head, Link, router, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { useState } from 'react';

const LABELS = {
    en: {
        backToDomains:      'Back to Domains',
        resultsFor:         (q) => <>Results for <span className="text-brand-500">"{q}"</span></>,
        checkTitle:         'Check Domain Availability',
        searchPlaceholder:  'Search another domain\u2026',
        searchBtn:          'Search',
        noResults:          'No results found. Try a different domain name.',
        enterDomain:        'Enter a domain name above to check availability.',
        available:          'Available',
        taken:              'Taken',
        premium:            'Premium',
        renewLabel:         'Renew:',
        register:           'Register',
        loginToRegister:    'Log in to Register',
        notAvailable:       'Not available',
        availableCount:     (n) => `${n} domain${n !== 1 ? 's' : ''} available`,
        notAvailableHeader: 'Not available',
        readyTitle:         'Ready to register?',
        readyDesc:          'Log in or create a free account to complete your order.',
        readyBtn:           'Log in to Register',
        metaTitle:          (q) => q ? `"${q}" \u2014 Domain Search` : 'Check Domain Availability',
    },
    pl: {
        backToDomains:      'Powr\u00f3t do domen',
        resultsFor:         (q) => <>Wyniki dla <span className="text-brand-500">"{q}"</span></>,
        checkTitle:         'Sprawdź dostępność domeny',
        searchPlaceholder:  'Szukaj innej domeny\u2026',
        searchBtn:          'Szukaj',
        noResults:          'Brak wynik\u00f3w. Spr\u00f3buj innej nazwy.',
        enterDomain:        'Wpisz nazwę domeny powyżej, aby sprawdzić dostępność.',
        available:          'Dostępna',
        taken:              'Zajęta',
        premium:            'Premium',
        renewLabel:         'Odnowienie:',
        register:           'Zarejestruj',
        loginToRegister:    'Zaloguj i zarejestruj',
        notAvailable:       'Niedostępna',
        availableCount:     (n) => `${n} domen${n === 1 ? 'a' : n < 5 ? 'y' : ''} dostępn${n === 1 ? 'a' : 'ych'}`,
        notAvailableHeader: 'Niedostępne',
        readyTitle:         'Gotowy do rejestracji?',
        readyDesc:          'Zaloguj się lub utwórz bezpłatne konto, aby sfinalizować zamówienie.',
        readyBtn:           'Zaloguj i zarejestruj',
        metaTitle:          (q) => q ? `"${q}" \u2014 Wyszukiwanie domeny` : 'Sprawdź dostępność domeny',
    },
    pt: {
        backToDomains:      'Voltar aos Dom\u00ednios',
        resultsFor:         (q) => <>Resultados para <span className="text-brand-500">"{q}"</span></>,
        checkTitle:         'Verificar Disponibilidade de Dom\u00ednio',
        searchPlaceholder:  'Pesquisar outro dom\u00ednio\u2026',
        searchBtn:          'Pesquisar',
        noResults:          'Nenhum resultado encontrado. Tente um nome diferente.',
        enterDomain:        'Insira um nome de dom\u00ednio acima para verificar a disponibilidade.',
        available:          'Dispon\u00edvel',
        taken:              'Indispon\u00edvel',
        premium:            'Premium',
        renewLabel:         'Renova\u00e7\u00e3o:',
        register:           'Registar',
        loginToRegister:    'Iniciar sess\u00e3o para registar',
        notAvailable:       'Indispon\u00edvel',
        availableCount:     (n) => `${n} dom\u00ednio${n !== 1 ? 's' : ''} dispon\u00edvel`,
        notAvailableHeader: 'Indispon\u00edvel',
        readyTitle:         'Pronto para registar?',
        readyDesc:          'Inicie sess\u00e3o ou crie uma conta gratuita para concluir a sua encomenda.',
        readyBtn:           'Iniciar sess\u00e3o e registar',
        metaTitle:          (q) => q ? `"${q}" \u2014 Pesquisa de Dom\u00ednio` : 'Verificar Disponibilidade de Dom\u00ednio',
    },
};

function SearchBar({ initialQuery, l }) {
    const [q, setQ] = useState(initialQuery);

    function handleSubmit(e) {
        e.preventDefault();
        if (!q.trim()) return;
        router.get(route('domains.check'), { q: q.trim() });
    }

    return (
        <form onSubmit={handleSubmit} className="flex flex-col sm:flex-row gap-3 w-full max-w-2xl">
            <input
                type="text"
                value={q}
                onChange={e => setQ(e.target.value)}
                placeholder={l.searchPlaceholder}
                className="flex-1 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-2.5 text-sm text-neutral-900 dark:text-white placeholder:text-neutral-400 shadow-sm focus:outline-none focus:ring-2 focus:ring-brand-500 transition"
            />
            <button
                type="submit"
                className="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20 whitespace-nowrap"
            >
                {l.searchBtn}
            </button>
        </form>
    );
}

function ResultRow({ result, auth, l }) {
    const symbol = result.currency === 'GBP' ? '£' : result.currency === 'EUR' ? '€' : '$';
    const orderUrl = auth
        ? route('domains.order') + `?domain=${encodeURIComponent(result.name)}&tld=${encodeURIComponent(result.tld)}`
        : route('login');

    return (
        <div className={`flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-2xl border px-5 py-4 transition-all ${
            result.is_available
                ? 'border-brand-500/25 bg-brand-500/5 dark:border-brand-500/20 dark:bg-brand-500/5'
                : result.error
                    ? 'border-amber-200 dark:border-amber-900/40 bg-amber-50/50 dark:bg-amber-900/10'
                    : 'border-neutral-200 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900'
        }`}>
            <div className="flex items-center gap-3">
                <span className={`inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-semibold ${
                    result.is_available
                        ? 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-400'
                        : result.error
                            ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400'
                            : 'bg-neutral-200 text-neutral-500 dark:bg-neutral-800 dark:text-neutral-400'
                }`}>
                    {result.is_available ? (
                        <><svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="3"><path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" /></svg> {l.available}</>
                    ) : result.error ? (
                        <><svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z" /></svg> {l.checkError ?? 'Check failed'}</>
                    ) : (
                        <><svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="3"><path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" /></svg> {l.taken}</>
                    )}
                </span>
                <span className="font-display font-bold text-neutral-900 dark:text-white text-lg">{result.domain}</span>
                <a
                    href={`https://www.${result.domain}`}
                    target="_blank"
                    rel="noopener noreferrer"
                    title={`Visit ${result.domain}`}
                    className="text-neutral-400 hover:text-brand-500 dark:text-neutral-500 dark:hover:text-brand-400 transition-colors"
                >
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                    </svg>
                </a>
                {result.is_premium && (
                    <span className="text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400 px-2 py-0.5 rounded-full font-medium">{l.premium}</span>
                )}
            </div>

            <div className="flex items-center gap-4">
                {result.is_available && result.register_price != null && (
                    <div className="text-right">
                        <div className="font-display text-lg font-bold text-neutral-900 dark:text-white">
                            {symbol}{Number(result.register_price).toFixed(2)}
                            <span className="text-xs font-normal text-neutral-400">/yr</span>
                        </div>
                        {result.renew_price != null && (
                            <div className="text-xs text-neutral-400">
                                {l.renewLabel} {symbol}{Number(result.renew_price).toFixed(2)}/yr
                            </div>
                        )}
                    </div>
                )}

                {result.is_available ? (
                    <Link
                        href={orderUrl}
                        className="inline-flex items-center gap-1.5 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-600 active:scale-95 transition-all shadow-md shadow-brand-500/20 whitespace-nowrap"
                    >
                        {auth ? l.register : l.loginToRegister}
                        <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </Link>
                ) : (
                    <span className="text-sm text-neutral-400 dark:text-neutral-500 italic">{l.notAvailable}</span>
                )}
            </div>
        </div>
    );
}

export default function DomainsCheck({ query = '', results = [], auth }) {
    const { footer, locale } = usePage().props;
    const l = LABELS[locale] ?? LABELS.en;
    const available   = results.filter(r => r.is_available);
    const unavailable = results.filter(r => !r.is_available);

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head title={l.metaTitle(query)} />

            {/* Header strip */}
            <section className="relative overflow-hidden border-b border-neutral-100 dark:border-neutral-800 pt-24 pb-10">
                <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50/30 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
                <div className="absolute top-0 right-0 w-64 h-64 bg-brand-500/10 rounded-full blur-3xl pointer-events-none hidden md:block" aria-hidden="true" />
                <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('domains.index')}
                        className="inline-flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-white transition-colors mb-4"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2"><path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" /></svg>
                        {l.backToDomains}
                    </Link>
                    <h1 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-5">
                        {query ? l.resultsFor(query) : l.checkTitle}
                    </h1>
                    <SearchBar initialQuery={query} l={l} />
                </div>
            </section>

            {/* Results */}
            <section className="py-12 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {results.length === 0 && query && (
                        <p className="text-center text-neutral-500 dark:text-neutral-400 py-16">
                            {l.noResults}
                        </p>
                    )}
                    {results.length === 0 && !query && (
                        <p className="text-center text-neutral-500 dark:text-neutral-400 py-16">
                            {l.enterDomain}
                        </p>
                    )}

                    {available.length > 0 && (
                        <div className="space-y-3 mb-10">
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-green-600 dark:text-green-400 mb-3">
                                {l.availableCount(available.length)}
                            </p>
                            {available.map(r => <ResultRow key={r.domain} result={r} auth={auth} l={l} />)}
                        </div>
                    )}

                    {unavailable.length > 0 && (
                        <div className="space-y-3">
                            <p className="text-xs font-semibold uppercase tracking-[0.12em] text-neutral-400 dark:text-neutral-500 mb-3">
                                {l.notAvailableHeader}
                            </p>
                            {unavailable.map(r => <ResultRow key={r.domain} result={r} auth={auth} l={l} />)}
                        </div>
                    )}

                    {!auth && results.some(r => r.is_available) && (
                        <div className="mt-8 rounded-2xl border border-brand-500/20 bg-brand-500/5 dark:bg-brand-500/10 px-6 py-5 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div>
                                <p className="font-semibold text-neutral-900 dark:text-white text-sm">{l.readyTitle}</p>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400 mt-0.5">
                                    {l.readyDesc}
                                </p>
                            </div>
                            <Link
                                href={route('login')}
                                className="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-brand-500 text-white font-semibold text-sm hover:bg-brand-600 active:scale-95 transition-all shadow-md shadow-brand-500/20 whitespace-nowrap"
                            >
                                {l.readyBtn}
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                </svg>
                            </Link>
                        </div>
                    )}
                </div>
            </section>
        </MarketingLayout>
    );
}
