const PROJECTS = [
    {
        title: 'Sklep z modą',
        tag: 'E-commerce',
        desc: 'WooCommerce → custom React + Laravel. Wzrost konwersji o 34%.',
        gradient: 'from-brand-400 to-brand-600',
    },
    {
        title: 'Kancelaria prawna',
        tag: 'Wizytówka',
        desc: 'Strona budująca autorytet, formularz kontaktowy z rezerwacją terminu.',
        gradient: 'from-neutral-600 to-neutral-900',
    },
    {
        title: 'Panel zarządzania flotą',
        tag: 'Aplikacja webowa',
        desc: 'SPA z real-time mapą, raportami PDF i integracją z GPS API.',
        gradient: 'from-brand-300 to-neutral-700',
    },
];

export default function Portfolio() {
    return (
        <section id="portfolio" className="py-20 flex items-center h-screen md:py-28 bg-neutral-50 dark:bg-neutral-900">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-14 reveal">
                    <span className="section-label">Portfolio</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        Wybrane realizacje
                    </h2>
                    <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-xl mx-auto">
                        Trzy projekty, z których jesteśmy najbardziej dumni.
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 reveal">
                    {PROJECTS.map(p => (
                        <article key={p.title} className="group rounded-2xl overflow-hidden border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-950 hover:shadow-xl dark:hover:shadow-neutral-900 transition-all">
                            <div className={`aspect-video bg-gradient-to-br ${p.gradient} relative overflow-hidden`}>
                                <div className="absolute inset-0 p-5 flex flex-col justify-end">
                                    <span className="inline-block px-2 py-0.5 rounded text-xs font-semibold bg-white/20 text-white w-fit mb-2">{p.tag}</span>
                                </div>
                            </div>
                            <div className="p-5">
                                <h3 className="font-semibold text-neutral-900 dark:text-white mb-1">{p.title}</h3>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-4">{p.desc}</p>
                                <a href="#" className="text-sm font-semibold text-brand-500 hover:text-brand-600 inline-flex items-center gap-1">
                                    Zobacz szczegóły
                                    <svg className="w-3.5 h-3.5 transition-transform group-hover:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                    </svg>
                                </a>
                            </div>
                        </article>
                    ))}
                </div>
            </div>
        </section>
    );
}
