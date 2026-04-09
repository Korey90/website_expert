import { usePage } from '@inertiajs/react';

const ICONS = [
    <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
    </svg>,
    <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
    </svg>,
    <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" d="M7 12l3-3 3 3 4-4M8 21l4-4 4 4M3 4h18M4 4h16v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z" />
    </svg>,
    <svg className="w-5 h-5 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
        <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
    </svg>,
];

const DEFAULTS = {
    badge:    { en: 'About Us',                                                          pl: 'O nas' },
    title:    { en: 'A Digital Agency That Treats You Like a Partner',                   pl: 'Agencja cyfrowa, która traktuje Cię jak partnera' },
    subtitle: { en: 'Based in Manchester. Working with businesses across the UK.',       pl: 'Siedziba w Manchesterze. Współpracujemy z firmami w całym UK.' },
    body:     { en: '<p>We\'re a small, dedicated team of developers, designers, and digital marketers who genuinely care about your results. No account managers passing your project around — you work directly with the people building your website.</p><p>Since 2014, we\'ve delivered over 200 projects for businesses ranging from sole traders to multi-site enterprises. We\'re proud of every one of them.</p>',
                pl: '<p>Jesteśmy małym, zaangażowanym zespołem programistów, projektantów i marketerów, którym naprawdę zależy na Twoich wynikach. Żadnych pośredników — pracujesz bezpośrednio z ludźmi tworzącymi Twoją stronę.</p><p>Od 2014 roku zrealizowaliśmy ponad 200 projektów dla firm — od jednoosobowych działalności po rozbudowane przedsiębiorstwa.</p>' },
    stats: [
        { value: '200+', label_en: 'Projects Delivered',  label_pl: 'Zrealizowanych projektów' },
        { value: '98%',  label_en: 'Client Satisfaction', label_pl: 'Zadowolonych klientów' },
        { value: '10+',  label_en: 'Years Experience',    label_pl: 'Lat doświadczenia' },
    ],
    highlights: [
        { title_en: 'Speed',       title_pl: 'Szybkość',       body_en: 'Delivered in 2–6 weeks. Deadlines are not suggestions.',                    body_pl: 'Realizacje w 2–6 tygodniach. Terminy to nie sugestie.' },
        { title_en: 'Security',    title_pl: 'Bezpieczeństwo', body_en: "Audits, SSL, GDPR – your data and your clients' data are protected.",        body_pl: 'Audyty, SSL, GDPR – Twoje dane i dane klientów są chronione.' },
        { title_en: 'Results',     title_pl: 'Wyniki',         body_en: 'Conversion optimisation and SEO built in from day one.',                    body_pl: 'Optymalizacja konwersji i SEO wbudowane od pierwszego dnia.' },
        { title_en: 'Partnership', title_pl: 'Partnerstwo',    body_en: "We don't disappear after launch. We're your long-term tech partner.",        body_pl: 'Nie znikamy po wdrożeniu. Jesteśmy Twoim długoterminowym tech-partnerem.' },
    ],
};

export default function About({ data }) {
    const { locale = 'en' } = usePage().props;

    // Resolve per-locale key: t(item, 'label') → item.label_pl → item.label_en → item.label
    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d          = data ?? {};
    const extra      = d.extra ?? {};
    const stats      = extra.stats?.length      ? extra.stats      : DEFAULTS.stats;
    const highlights = extra.highlights?.length ? extra.highlights : DEFAULTS.highlights;

    const badge    = t(extra, 'section_label') || DEFAULTS.badge[locale]    || DEFAULTS.badge.en;
    const title    = d.title    || DEFAULTS.title[locale]    || DEFAULTS.title.en;
    const subtitle = d.subtitle || DEFAULTS.subtitle[locale] || DEFAULTS.subtitle.en;
    const body     = d.body     || DEFAULTS.body[locale]     || DEFAULTS.body.en;

    return (
        <section id="o-nas" className="py-16 sm:py-20 md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="grid lg:grid-cols-2 gap-12 lg:gap-20 items-center">

                    {/* Text column */}
                    <div className="reveal">
                        <span className="section-label">{badge}</span>
                        <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 mb-3 text-neutral-900 dark:text-white leading-tight">
                            {title}
                        </h2>
                        {subtitle && (
                            <p className="text-brand-500 font-medium text-sm mb-5">{subtitle}</p>
                        )}
                        <div
                            className="text-neutral-600 dark:text-neutral-400 text-base leading-relaxed [&_p]:mb-4 last:[&_p]:mb-0"
                            dangerouslySetInnerHTML={{ __html: body }}
                        />

                        {/* Stats */}
                        <div className="grid grid-cols-3 gap-2 sm:gap-4 mt-10">
                            {stats.map((s, i) => (
                                <div key={i} className="text-center p-2 sm:p-4 rounded-xl bg-neutral-50 dark:bg-neutral-900 overflow-hidden min-w-0">
                                    <p className="font-display text-xl sm:text-2xl md:text-3xl font-extrabold text-brand-500 truncate">{s.value}</p>
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400 mt-1 leading-tight">{t(s, 'label')}</p>
                                </div>
                            ))}
                        </div>

                        {d.button_text && d.button_url && (
                            <div className="mt-8">
                                <a
                                    href={d.button_url}
                                    className="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 transition-colors"
                                >
                                    {d.button_text}
                                </a>
                            </div>
                        )}
                    </div>

                    {/* Highlights grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4 reveal">
                        {highlights.map((v, i) => (
                            <div key={i} className="p-5 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/50 transition-colors group">
                                <div className="flex items-start gap-3 sm:block">
                                    <div className="shrink-0 w-10 h-10 rounded-xl bg-brand-500/10 flex items-center justify-center sm:mb-3 group-hover:bg-brand-500/20 transition-colors">
                                        {ICONS[i % ICONS.length]}
                                    </div>
                                    <div>
                                        <h3 className="font-semibold text-neutral-900 dark:text-white mb-1">{t(v, 'title')}</h3>
                                        <p className="text-sm text-neutral-500 dark:text-neutral-400">{t(v, 'body')}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </div>
        </section>
    );
}

