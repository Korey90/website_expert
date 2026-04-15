import { usePage } from '@inertiajs/react';

const ICON_PATHS = {
    'monitor': <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />,
    'shopping-cart': <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />,
    'code': <path strokeLinecap="round" strokeLinejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />,
    'search': <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />,
    'bar-chart': <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />,
    'settings': <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />,
    'shield': <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />,
    'pencil': <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />,
    'zap': <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />,
    'file-text': <><path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></>,
};

const DEFAULTS = {
    title:         { en: 'Everything You Need to Succeed Online',    pl: 'Wszystko, czego potrzebujesz do sukcesu online' },
    subtitle:      { en: 'From your first website to a full digital transformation — we have the expertise to make it happen.',
                     pl: 'Od prostej wizytówki po rozbudowaną aplikację – każde rozwiązanie budujemy z myślą o realnych wynikach.' },
    button_text:   { en: 'Calculate Project Cost',                   pl: 'Oblicz koszt projektu' },
    button_url:    '#calculator',
    section_label: { en: 'Services',                                  pl: 'Oferta' },
    services: [
        { icon: 'monitor',        title_en: 'Brochure Websites',     title_pl: 'Strony wizytówkowe',     description_en: 'Professional, mobile-first websites that create the right first impression.', description_pl: 'Profesjonalne strony firmowe, które budują zaufanie i generują zapytania.' },
        { icon: 'shopping-cart',  title_en: 'E-Commerce Stores',     title_pl: 'Sklepy e-commerce',      description_en: 'Sell online with confidence. WooCommerce and headless solutions.',            description_pl: 'Szybkie, bezpieczne sklepy online z integracją płatności.' },
        { icon: 'code',           title_en: 'Web Applications',      title_pl: 'Aplikacje internetowe',  description_en: 'Bespoke Laravel and React applications. Portals, SaaS, booking systems.',     description_pl: 'Dedykowane aplikacje Laravel i React. Portale, SaaS, rezerwacje.' },
        { icon: 'search',         title_en: 'SEO & Marketing',       title_pl: 'SEO i Marketing',        description_en: 'Rank higher, attract more visitors, convert them into customers.',              description_pl: 'Zaistniej w Google. Audyty SEO, optymalizacja on-page, content.' },
        { icon: 'bar-chart',      title_en: 'Google Ads (PPC)',      title_pl: 'Google Ads (PPC)',       description_en: 'Targeted pay-per-click campaigns that deliver measurable ROI.',                description_pl: 'Kampanie płatne z realnym ROI – konfiguracja i optymalizacja.' },
        { icon: 'settings',       title_en: 'Website Maintenance',   title_pl: 'Opieka nad stroną',      description_en: 'Keep your site fast, secure, and up to date with our care plans.',             description_pl: 'Szybki hosting z SSL, backupami i opieką techniczną.' },
    ],
};

export default function Services({ data }) {
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
    const items        = (extra.services ?? DEFAULTS.services).filter(s => s.is_featured !== false);

    return (
        <section id="services" className="py-20 md:py-28 bg-white dark:bg-neutral-950">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-14 reveal">
                    <span className="section-label">{sectionLabel}</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                    <p className="mt-4 text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                        {subtitle}
                    </p>
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5 reveal">
                    {items.map((s, i) => {
                        const itemTitle = t(s, 'title')       || s.title       || '';
                        const itemBody  = t(s, 'description') || s.description || '';
                        const iconPath  = ICON_PATHS[s.icon]  ?? ICON_PATHS['settings'];
                        const href      = s.is_active !== false ? (s.link ?? null) : null;

                        return (
                            <div key={i} className="group p-6 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-lg hover:shadow-brand-500/5 transition-all cursor-default">
                                <div className="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center mb-4 group-hover:bg-brand-500/20 transition-colors">
                                    <svg className="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                        {iconPath}
                                    </svg>
                                </div>
                                <div className="flex items-center gap-1.5 mb-2">
                                    <h3 className="font-semibold text-neutral-900 dark:text-white">{itemTitle}</h3>
                                    {s.price_from && (
                                        <span className="text-xs text-brand-500 font-medium ml-auto shrink-0">{s.price_from}</span>
                                    )}
                                </div>
                                <p className="text-sm text-neutral-500 dark:text-neutral-400">{itemBody}</p>
                                {href ? (
                                    <a
                                        href={href}
                                        aria-label={locale === 'pl' ? `Dowiedz się więcej o ${itemTitle}` : locale === 'pt' ? `Saiba mais sobre ${itemTitle}` : `Learn more about ${itemTitle}`}
                                        className="mt-3 inline-flex items-center gap-1 text-xs text-brand-500 font-medium hover:underline"
                                    >
                                        {locale === 'pl' ? `Więcej o ${itemTitle}` : locale === 'pt' ? `Mais Sobre ${itemTitle}` : `More About ${itemTitle}`}
                                        <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                            <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                ) : s.link ? (
                                    <span className="mt-3 inline-block text-xs text-neutral-400 dark:text-neutral-600 italic">
                                        {locale === 'pl' ? 'Wkrótce' : locale === 'pt' ? 'Em breve' : 'Coming soon'}
                                    </span>
                                ) : null}
                            </div>
                        );
                    })}
                </div>

                <div className="text-center mt-10 reveal">
                    <a
                        href={buttonUrl}
                        className="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                    >
                        {buttonText}
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>
    );
}

