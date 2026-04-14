import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

const GRADIENTS = [
    'from-brand-400 to-brand-600',
    'from-neutral-600 to-neutral-900',
    'from-brand-300 to-neutral-700',
    'from-brand-500 to-neutral-800',
    'from-neutral-500 to-brand-700',
    'from-brand-200 to-neutral-600',
];

export default function PortfolioIndex({ locale: localeProp, projects, auth }) {
    useScrollReveal('.reveal');

    const { navbar, footer } = usePage().props;
    const locale = localeProp ?? 'en';

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? '';

    const labels = {
        en: {
            pageTitle:   'Our Portfolio',
            subtitle:    'A selection of websites, e-commerce platforms and digital projects we have delivered.',
            viewCase:    'View case study',
            comingSoon:  'Coming soon',
        },
        pl: {
            pageTitle:   'Nasze portfolio',
            subtitle:    'Wybrane strony internetowe, platformy e-commerce i projekty cyfrowe, które zrealizowaliśmy.',
            viewCase:    'Zobacz szczegóły',
            comingSoon:  'Wkrótce',
        },
        pt: {
            pageTitle:   'O Nosso Portfolio',
            subtitle:    'Uma seleção de sites, plataformas de e-commerce e projetos digitais que entregámos.',
            viewCase:    'Ver caso de estudo',
            comingSoon:  'Em breve',
        },
    };

    const l = labels[locale] ?? labels.en;

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>Portfolio – Website Expert</title>
                <meta name="description" content="Browse our portfolio of web design, e-commerce and digital marketing projects delivered for businesses across Northern Ireland and the UK." />
            </Head>

            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="text-center mb-12 reveal">
                        <span className="section-label">Portfolio</span>
                        <h1 className="font-display text-3xl sm:text-4xl md:text-5xl font-bold mt-3 text-neutral-900 dark:text-white">
                            {l.pageTitle}
                        </h1>
                        <p className="mt-4 text-base text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                            {l.subtitle}
                        </p>
                    </div>

                    {/* Grid */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 reveal">
                        {projects.map((p, i) => {
                            const itemTitle  = t(p, 'title');
                            const itemTag    = t(p, 'tag');
                            const itemDesc   = t(p, 'desc');
                            const itemResult = t(p, 'result');
                            const href       = p.is_active ? (p.link ?? `/portfolio/${p.slug}`) : null;
                            const tags       = Array.isArray(p.tags) ? p.tags : [];

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
                                        <h2 className="font-semibold text-neutral-900 dark:text-white mb-2 leading-snug">{itemTitle}</h2>
                                        <p className="text-sm text-neutral-500 dark:text-neutral-400 mb-3 leading-relaxed">{itemDesc}</p>

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
                                                    {l.viewCase}
                                                    <svg className="w-3.5 h-3.5 transition-transform group-hover/link:translate-x-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                                                    </svg>
                                                </a>
                                            ) : (
                                                <span className="ml-auto text-sm text-neutral-400 dark:text-neutral-600 italic">{l.comingSoon}</span>
                                            )}
                                        </div>
                                    </div>
                                </article>
                            );
                        })}
                    </div>
                </div>
            </section>
        </MarketingLayout>
    );
}
