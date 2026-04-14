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
        back:     '← Back to Services',
        price:    'Starting from',
        getQuote: 'Get a Free Quote',
        ctaDesc:  "Ready to get started? Get in touch and we'll send you a tailored proposal.",
    },
    pl: {
        back:     '← Powrót do usług',
        price:    'Cena od',
        getQuote: 'Zapytaj o wycenę',
        ctaDesc:  'Gotowy, żeby zacząć? Skontaktuj się z nami, a wyślemy Ci spersonalizowaną ofertę.',
    },
    pt: {
        back:     '← Voltar aos Serviços',
        price:    'A partir de',
        getQuote: 'Pedir Orçamento',
        ctaDesc:  'Pronto para começar? Entre em contacto e enviaremos uma proposta personalizada.',
    },
};

export default function ServicesShow({ locale: localeProp, item, auth }) {
    useScrollReveal('.reveal');

    const { navbar, footer } = usePage().props;
    const locale = localeProp ?? 'en';
    const l = labels[locale] ?? labels.en;

    const title = item?.[`title_${locale}`]       ?? item?.title_en       ?? '';
    const desc  = item?.[`description_${locale}`] ?? item?.description_en ?? '';
    const iconPath = ICON_PATHS[item?.icon] ?? ICON_PATHS['settings'];

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>{`${title} – Website Expert`}</title>
                <meta name="description" content={desc} />
            </Head>

            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    {/* Back link */}
                    <a
                        href="/services"
                        className="inline-flex items-center text-sm text-neutral-500 dark:text-neutral-400 hover:text-brand-500 mb-10 transition-colors"
                    >
                        {l.back}
                    </a>

                    {/* Icon + Title */}
                    <div className="flex items-start gap-5 mb-8 reveal">
                        <div className="w-16 h-16 rounded-2xl bg-brand-500/10 flex items-center justify-center shrink-0">
                            <svg className="w-8 h-8 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                {iconPath}
                            </svg>
                        </div>
                        <div className="flex-1 min-w-0">
                            <h1 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white leading-tight mb-2">
                                {title}
                            </h1>
                            {item?.price_from && (
                                <p className="text-sm font-semibold text-brand-500">
                                    {l.price} <span className="text-base">{item.price_from}</span>
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Description */}
                    <div className="prose prose-neutral dark:prose-invert max-w-none mb-12 reveal">
                        <p className="text-base text-neutral-700 dark:text-neutral-300 leading-relaxed">{desc}</p>
                    </div>

                    {/* CTA box */}
                    <div className="rounded-2xl bg-brand-50 dark:bg-brand-950/20 border border-brand-100 dark:border-brand-900 px-7 py-6 reveal">
                        <p className="text-sm text-neutral-600 dark:text-neutral-400 mb-4">{l.ctaDesc}</p>
                        <a
                            href="/contact"
                            className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-md shadow-brand-500/20"
                        >
                            {l.getQuote}
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
