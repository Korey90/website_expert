const VALUES = [
    {
        icon: (
            <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
            </svg>
        ),
        title: 'Szybkość',
        body: 'Realizacje w 2–6 tygodniach. Deadlines to nie sugestie.',
    },
    {
        icon: (
            <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
            </svg>
        ),
        title: 'Bezpieczeństwo',
        body: 'Audyty, SSL, GDPR – Twoje dane i dane klientów są chronione.',
    },
    {
        icon: (
            <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
            </svg>
        ),
        title: 'Wyniki',
        body: 'Optymalizacja konwersji i SEO wbudowane od pierwszego dnia.',
    },
    {
        icon: (
            <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
            </svg>
        ),
        title: 'Partnerstwo',
        body: 'Nie znikamy po wdrożeniu. Jesteśmy Twoim tech-partnerem.',
    },
];

const STATS = [
    { value: '50+', label: 'projektów' },
    { value: '98%', label: 'zadow. klientów' },
    { value: '2tyg', label: 'min. czas realiz.' },
];

export default function About() {
    return (
        <section id="o-nas" className="py-20 flex items-center h-screen md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">

                    {/* Text */}
                    <div className="reveal">
                        <span className="section-label">O nas</span>
                        <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 mb-6 text-neutral-900 dark:text-white leading-tight">
                            Robimy internet,<br />który <span className="text-brand-500">pracuje dla Ciebie</span>
                        </h2>
                        <p className="text-neutral-600 dark:text-neutral-400 text-base leading-relaxed mb-5">
                            Jesteśmy pasjonatami nowych technologii z jednym celem: pomagać małym i średnim firmom rosnąć dzięki silnej obecności w sieci.
                            Nie sprzedajemy szablonów – tworzymy rozwiązania szyte na miarę.
                        </p>
                        <p className="text-neutral-600 dark:text-neutral-400 text-base leading-relaxed">
                            Łączymy nowoczesny design z czystym, wydajnym kodem. Każdy projekt ruszamy od Twojego biznesu – nie od naszego portfolio.
                        </p>

                        {/* Stats */}
                        <div className="grid grid-cols-3 gap-4 mt-10">
                            {STATS.map(s => (
                                <div key={s.label} className="text-center p-4 rounded-xl bg-neutral-50 dark:bg-neutral-900">
                                    <p className="font-display text-3xl font-extrabold text-brand-500">{s.value}</p>
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400 mt-1">{s.label}</p>
                                </div>
                            ))}
                        </div>
                    </div>

                    {/* Values grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 reveal">
                        {VALUES.map(v => (
                            <div key={v.title} className="p-5 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/50 transition-colors group">
                                <div className="w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center mb-3 group-hover:bg-brand-500/20 transition-colors">
                                    {v.icon}
                                </div>
                                <h3 className="font-semibold text-neutral-900 dark:text-white mb-1">{v.title}</h3>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">{v.body}</p>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}
