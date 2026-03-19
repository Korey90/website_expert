const SERVICES = [
    {
        title: 'Strona wizytówkowa',
        body: 'Profesjonalna strona firmowa, która buduje zaufanie i generuje zapytania.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />,
    },
    {
        title: 'Sklep e-commerce',
        body: 'Szybkie, bezpieczne sklepy online z integracją płatności i systemu zamówień.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />,
    },
    {
        title: 'SEO & Pozycjonowanie',
        body: 'Zaistniej w Google. Audyty SEO, optymalizacja on-page i strategia contentu.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />,
    },
    {
        title: 'Hosting WWW',
        badge: 'opcja',
        body: 'Szybki hosting z SSL, backupami i opieką techniczną w pakiecie.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />,
    },
    {
        title: 'Tworzenie treści',
        body: 'Copywriting, blog, opisy produktów – teksty sprzedające i przyjazne SEO.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />,
    },
    {
        title: 'Audyt bezpieczeństwa',
        body: 'Testy penetracyjne, luki OWASP Top 10, raporty wydajności i rekomendacje.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />,
    },
    {
        title: 'Google Ads',
        body: 'Kampanie płatne z realnym ROI – konfiguracja, optymalizacja i raportowanie.',
        icon: <><path strokeLinecap="round" strokeLinejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z" /><path strokeLinecap="round" strokeLinejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z" /></>,
    },
    {
        title: 'Meta / Pixel Ads',
        body: 'Reklamy na Facebooku i Instagramie – retargeting, lookalike, konwersje.',
        icon: <path strokeLinecap="round" strokeLinejoin="round" d="M7 4V2m10 2V2M3 10h18M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />,
    },
];

export default function Services() {
    return (
        <section id="oferta" className="py-20 md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-14 reveal">
                    <span className="section-label">Oferta</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        Co dla Ciebie robimy
                    </h2>
                    <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                        Od prostej wizytówki po rozbudowaną aplikację – każde rozwiązanie budujemy z myślą o realnych wynikach.
                    </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 reveal">
                    {SERVICES.map(s => (
                        <div key={s.title} className="group p-6 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-lg hover:shadow-brand-500/5 transition-all cursor-default">
                            <div className="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center mb-4 group-hover:bg-brand-500/20 transition-colors">
                                <svg className="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    {s.icon}
                                </svg>
                            </div>
                            <h3 className="font-semibold text-neutral-900 dark:text-white mb-2">
                                {s.title}
                                {s.badge && <span className="text-xs text-brand-500 font-normal ml-1">{s.badge}</span>}
                            </h3>
                            <p className="text-sm text-neutral-500 dark:text-neutral-400">{s.body}</p>
                        </div>
                    ))}
                </div>

                <div className="text-center mt-10 reveal">
                    <a
                        href="#kalkulator"
                        className="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                    >
                        Oblicz koszt swojego projektu
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    );
}
