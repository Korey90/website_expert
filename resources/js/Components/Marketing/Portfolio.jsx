import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    title:         { en: "Recent Work We're Proud Of",                          pl: 'Ostatnie projekty, z których jesteśmy dumni' },
    subtitle:      { en: 'Every project is different. Here are a few recent examples.', pl: 'Każdy projekt jest inny. Oto kilka ostatnich realizacji.' },
    button_text:   { en: 'View All Projects',                                   pl: 'Zobacz wszystkie projekty' },
    button_url:    '/portfolio',
    section_label: { en: 'Portfolio',                                           pl: 'Portfolio' },
    items: [
        {
            title_en: 'Solicitors Website Redesign',   title_pl: 'Redesign strony kancelarii',
            tag_en: 'Brochure Website',                tag_pl: 'Strona wizytówkowa',
            desc_en: 'WCAG AA-compliant redesign for a Manchester solicitors firm. +40% contact form conversions.',
            desc_pl: 'Redesign dla kancelarii z Manchesteru. +40% konwersji formularza kontaktowego.',
            result_en: '+40% contact form conversions',  result_pl: '+40% konwersji formularza',
            image: '/images/portfolio/hargreaves-solicitors.svg', link: '/portfolio/hargreaves-solicitors',
        },
        {
            title_en: 'B2B E-Commerce Platform',       title_pl: 'Platforma e-commerce B2B',
            tag_en: 'E-Commerce',                      tag_pl: 'E-Commerce',
            desc_en: 'Full B2B trade portal with 3,500+ SKUs, tiered pricing and ERP integration.',
            desc_pl: 'Portal handlowy B2B z 3 500+ produktami, cenami poziomowymi i integracją ERP.',
            result_en: '£80k online sales in first month', result_pl: '80 tys. £ sprzedaży w pierwszym miesiącu',
            image: '/images/portfolio/nts-direct.svg',  link: '/portfolio/nts-direct',
        },
        {
            title_en: 'Dental Practice Website',       title_pl: 'Strona kliniki dentystycznej',
            tag_en: 'Healthcare Website',              tag_pl: 'Strona medyczna',
            desc_en: 'CQC-compliant dental site with online booking and patient portal.',
            desc_pl: 'Strona gabinetu zgodna z CQC, rezerwacja online i portal pacjenta.',
            result_en: '60% more new patient enquiries', result_pl: '60% więcej zgłoszeń nowych pacjentów',
            image: '/images/portfolio/oakfield-dental.svg', link: '/portfolio/oakfield-dental',
        },
    ],
};

// Fallback gradient per index when no image
const GRADIENTS = [
    'from-brand-400 to-brand-600',
    'from-neutral-600 to-neutral-900',
    'from-brand-300 to-neutral-700',
];

export default function Portfolio({ data }) {
    const { locale = 'en' } = usePage().props;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d     = data ?? {};
    const extra = d.extra ?? {};

    const title        = d.title       || DEFAULTS.title[locale]       || DEFAULTS.title.en;
    const subtitle     = d.subtitle    || DEFAULTS.subtitle[locale]    || DEFAULTS.subtitle.en;
    const buttonText   = d.button_text || DEFAULTS.button_text[locale] || DEFAULTS.button_text.en;
    const buttonUrl    = d.button_url  || DEFAULTS.button_url;
    const sectionLabel = t(extra, 'section_label') || DEFAULTS.section_label[locale];
    const items        = extra.items ?? DEFAULTS.items;

    return (
        <section id="portfolio" className="py-16 sm:py-20 md:py-28 bg-neutral-100 dark:bg-neutral-800">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-10 sm:mb-14 reveal">
                    <span className="section-label">{sectionLabel}</span>
                    <h2 className="font-display text-2xl sm:text-3xl md:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                    <p className="mt-3 sm:mt-4 text-sm sm:text-base text-neutral-500 dark:text-neutral-400 max-w-xl mx-auto">
                        {subtitle}
                    </p>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6 reveal">
                    {items.map((p, i) => {
                        const itemTitle  = t(p, 'title')  || p.title  || '';
                        const itemTag    = t(p, 'tag')    || p.tag    || '';
                        const itemDesc   = t(p, 'desc')   || p.desc   || '';
                        const itemResult = t(p, 'result') || p.result || '';
                        const href       = p.is_active !== false ? (p.link ?? '#') : null;
                        const tags       = Array.isArray(p.tags) ? p.tags : [];
                        const viewLabel  = locale === 'pl' ? 'Zobacz szczegóły' : 'View case study';

                        return (
                            <article key={i} className="group rounded-2xl overflow-hidden border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-950 hover:shadow-xl dark:hover:shadow-neutral-900 transition-all">
                                {/* Thumbnail */}
                                <div className={`aspect-video relative overflow-hidden ${!p.image ? `bg-linear-to-br ${GRADIENTS[i % GRADIENTS.length]}` : 'bg-neutral-200 dark:bg-neutral-800'}`}>
                                    {p.image ? (
                                        <img
                                            src={p.image}
                                            alt={itemTitle}
                                            className="w-full h-full object-cover object-top group-hover:scale-105 transition-transform duration-500"
                                            loading="lazy"
                                        />
                                    ) : null}
                                    {/* Tag overlay */}
                                    <div className="absolute inset-0 bg-linear-to-t from-black/40 via-transparent to-transparent" />
                                    <div className="absolute bottom-0 left-0 p-4">
                                        <span className="inline-block px-2.5 py-1 rounded-md text-xs font-semibold bg-black/40 backdrop-blur-sm text-white ring-1 ring-white/20">
                                            {itemTag}
                                        </span>
                                    </div>
                                </div>

                                {/* Body */}
                                <div className="p-5">
                                    {p.client && (
                                        <p className="text-xs text-brand-500 font-semibold uppercase tracking-wide mb-1">{p.client}</p>
                                    )}
                                    <h3 className="font-semibold text-neutral-900 dark:text-white mb-2 leading-snug">{itemTitle}</h3>
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-3 leading-relaxed">{itemDesc}</p>

                                    {/* Tags */}
                                    {tags.length > 0 && (
                                        <div className="flex flex-wrap gap-1.5 mb-4">
                                            {tags.map((tag, ti) => (
                                                <span key={ti} className="text-xs px-2 py-0.5 rounded-full bg-neutral-100 dark:bg-neutral-800 text-neutral-600 dark:text-neutral-400">
                                                    {tag}
                                                </span>
                                            ))}
                                        </div>
                                    )}

                                    <div className="flex items-center justify-between">
                                        {itemResult && (
                                            <span className="text-xs font-semibold text-emerald-600 dark:text-emerald-400 bg-emerald-50 dark:bg-emerald-950/30 px-2 py-0.5 rounded-full">
                                                {itemResult}
                                            </span>
                                        )}
                                        {href ? (
                                            <a
                                                href={href}
                                                className="ml-auto text-sm font-semibold text-brand-500 hover:text-brand-600 inline-flex items-center gap-1 group/link"
                                            >
                                                {p.client}
                                                <svg className="w-3.5 h-3.5 transition-transform group-hover/link:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                </svg>
                                            </a>
                                        ) : (
                                            <span className="ml-auto text-sm text-neutral-400 dark:text-neutral-600 italic">
                                                {locale === 'pl' ? 'Wkrótce' : 'Coming soon'}
                                            </span>
                                        )}
                                    </div>
                                </div>
                            </article>
                        );
                    })}
                </div>


                {/* CTA Button /portfolio*/}
                {buttonText && buttonUrl && (
                    <div className="text-center mt-10 reveal">
                        <a
                            href={buttonUrl}
                            className="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 font-semibold hover:border-brand-500 hover:text-brand-500 active:scale-95 transition-all"
                        >
                            {buttonText}
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                )}
            </div>
        </section>
    );
}

