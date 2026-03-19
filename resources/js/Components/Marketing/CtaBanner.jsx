export default function CtaBanner() {
    return (
        <section id="cta-main" className="py-16 bg-neutral-950 dark:bg-white relative overflow-hidden">
            {/* Subtle brand ambient */}
            <div className="absolute inset-0 bg-gradient-to-br from-brand-950/60 dark:from-brand-500/5 via-transparent to-transparent" aria-hidden="true" />
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-[600px] h-40 bg-brand-500/10 blur-3xl rounded-full" aria-hidden="true" />
            <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center reveal">
                <h2 className="font-display text-3xl sm:text-4xl font-bold text-white dark:text-neutral-900 mb-4">
                    Masz pomysł na projekt?
                </h2>
                <p className="text-neutral-400 dark:text-neutral-500 text-lg max-w-2xl mx-auto mb-8">
                    Skorzystaj z kalkulatora i dowiedz się, ile może kosztować Twoja strona już dziś.
                    Bez zobowiązań, bez spamu.
                </p>
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <a
                        href="#kalkulator"
                        className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-brand-500 text-white font-bold text-base hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/25"
                    >
                        Oblicz koszt projektu
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    <a
                        href="#kontakt"
                        className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border border-neutral-700 dark:border-neutral-300 text-neutral-300 dark:text-neutral-700 font-bold text-base hover:border-brand-500 hover:text-brand-500 active:scale-95 transition-all"
                    >
                        Napisz do nas
                    </a>
                </div>
            </div>
        </section>
    );
}
