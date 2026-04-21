import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

const ICON_PATHS = {
    'monitor':       <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />,
    'shopping-cart': <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />,
    'code':          <path strokeLinecap="round" strokeLinejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />,
    'search':        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />,
    'bar-chart':     <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />,
    'settings':      <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />,
    'shield':        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />,
    'pencil':        <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />,
    'zap':           <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />,
    'file-text':     <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />,
};

const labels = {
    en: {
        pageTitle:    'Our Services',
        subtitle:     'Everything you need to grow your business online — from your first website to a full digital transformation.',
        sectionLabel: 'Services',
        learnMore:    'Learn more',
        comingSoon:   'Coming soon',
        ctaTitle:     'Not Sure Where to Start?',
        ctaDesc:      "Tell us about your project and we'll recommend the right service and send you a free, no-obligation quote.",
        ctaButton:    'Get a Free Quote',
    },
    pl: {
        pageTitle:    'Nasze usługi',
        subtitle:     'Wszystko, czego potrzebujesz do sukcesu online — od prostej wizytówki po rozbudowaną aplikację.',
        sectionLabel: 'Oferta',
        learnMore:    'Dowiedz się więcej',
        comingSoon:   'Wkrótce',
        ctaTitle:     'Nie wiesz od czego zacząć?',
        ctaDesc:      'Opisz swój projekt, a my dobierzemy odpowiednią usługę i wyślemy bezpłatną wycenę bez zobowiązań.',
        ctaButton:    'Zapytaj o wycenę',
    },
    pt: {
        pageTitle:    'Os Nossos Serviços',
        subtitle:     'Tudo o que precisa para crescer online — do primeiro site a uma transformação digital completa.',
        sectionLabel: 'Serviços',
        learnMore:    'Saiba mais',
        comingSoon:   'Em breve',
        ctaTitle:     'Não sabe por onde começar?',
        ctaDesc:      'Conte-nos o seu projeto e recomendaremos o serviço certo, enviando um orçamento gratuito e sem compromisso.',
        ctaButton:    'Pedir Orçamento',
    },
};

export default function ServicesIndex({ locale: localeProp, items, auth }) {
    useScrollReveal('.reveal');

    const { footer } = usePage().props;
    const locale = localeProp ?? 'en';
    const l = labels[locale] ?? labels.en;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? '';

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head>
                <title>Services – Website Expert</title>
                <meta name="description" content="Professional web design, e-commerce, SEO, Google Ads, Meta Ads, content creation and website maintenance services." />
            </Head>

            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-14 reveal">
                        <span className="section-label">{l.sectionLabel}</span>
                        <h1 className="font-display text-3xl sm:text-4xl md:text-5xl font-bold mt-3 text-neutral-900 dark:text-white">
                            {l.pageTitle}
                        </h1>
                        <p className="mt-4 text-base text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                            {l.subtitle}
                        </p>
                    </div>

                    {/* Grid */}
                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 reveal">
                        {items.map((s, i) => {
                            const title = t(s, 'title');
                            const desc  = t(s, 'description');
                            const iconPath = ICON_PATHS[s.icon] ?? ICON_PATHS['settings'];
                            const href  = s.is_active ? `/services/${s.slug}` : null;

                            return (
                                <article
                                    key={i}
                                    className="group flex flex-col p-7 rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-neutral-50 dark:bg-neutral-900 hover:border-brand-500/60 hover:shadow-xl hover:shadow-brand-500/5 transition-all"
                                >
                                    {/* Icon */}
                                    <div className="w-12 h-12 rounded-2xl bg-brand-500/10 flex items-center justify-center mb-5 group-hover:bg-brand-500/20 transition-colors shrink-0">
                                        <svg className="w-6 h-6 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                            {iconPath}
                                        </svg>
                                    </div>

                                    {/* Title + price */}
                                    <div className="flex items-start justify-between gap-2 mb-2">
                                        <h2 className="font-semibold text-lg text-neutral-900 dark:text-white leading-snug">{title}</h2>
                                        {s.price_from && (
                                            <span className="text-xs text-brand-500 font-semibold whitespace-nowrap mt-0.5 shrink-0">
                                                {locale === 'en' ? 'from ' : locale === 'pl' ? 'od ' : 'a partir de '}{s.price_from}
                                            </span>
                                        )}
                                    </div>

                                    {/* Description */}
                                    <p className="text-sm text-neutral-500 dark:text-neutral-400 leading-relaxed flex-1 mb-5">{desc}</p>

                                    {/* CTA */}
                                    {href ? (
                                        <a
                                            href={href}
                                            className="inline-flex items-center gap-1.5 text-sm font-semibold text-brand-500 hover:text-brand-600 transition-colors group/link"
                                        >
                                            {l.learnMore}
                                            <svg className="w-3.5 h-3.5 transition-transform group-hover/link:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                            </svg>
                                        </a>
                                    ) : (
                                        <span className="text-sm text-neutral-400 dark:text-neutral-600 italic">{l.comingSoon}</span>
                                    )}
                                </article>
                            );
                        })}
                    </div>

                    {/* CTA */}
                    <div className="mt-20 rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-neutral-800 px-8 py-12 text-center reveal">
                        <h2 className="font-display text-2xl sm:text-3xl font-bold text-neutral-900 dark:text-white mb-3">
                            {l.ctaTitle}
                        </h2>
                        <p className="text-neutral-500 dark:text-neutral-400 max-w-xl mx-auto mb-7 text-sm sm:text-base">
                            {l.ctaDesc}
                        </p>
                        <a
                            href="/contact"
                            className="inline-flex items-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold text-sm hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20"
                        >
                            {l.ctaButton}
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
