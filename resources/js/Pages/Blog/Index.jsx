import { Head, Link, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

const labels = {
    en: {
        pageTitle: 'Blog',
        subtitle: 'Guides and advice on websites, SEO and digital marketing for UK businesses.',
        readMore: 'Read more →',
        empty: 'No articles yet — check back soon.',
    },
    pl: {
        pageTitle: 'Blog',
        subtitle: 'Poradniki i porady dotyczące stron internetowych, SEO i marketingu cyfrowego dla firm w UK.',
        readMore: 'Czytaj dalej →',
        empty: 'Jeszcze brak artykułów — zajrzyj tu wkrótce.',
    },
    pt: {
        pageTitle: 'Blog',
        subtitle: 'Guias e conselhos sobre sites, SEO e marketing digital para empresas no Reino Unido.',
        readMore: 'Ler mais →',
        empty: 'Ainda sem artigos — volte em breve.',
    },
};

export default function BlogIndex({ locale: localeProp, articles }) {
    useScrollReveal('.reveal');

    const { footer } = usePage().props;
    const locale = localeProp ?? 'en';
    const l = labels[locale] ?? labels.en;
    const items = articles?.data ?? [];
    const links = articles?.links ?? [];

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>{l.pageTitle}</title>
                <meta name="description" content={l.subtitle} />
            </Head>

            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <nav className="mb-6 text-sm text-neutral-500 dark:text-neutral-400" aria-label="Breadcrumb">
                        <ol className="flex items-center gap-1.5">
                            <li><Link href="/" className="hover:text-brand-500 transition-colors">{locale === 'pl' ? 'Strona główna' : 'Home'}</Link></li>
                            <li aria-hidden="true">›</li>
                            <li className="text-neutral-700 dark:text-neutral-300" aria-current="page">Blog</li>
                        </ol>
                    </nav>

                    <div className="text-center mb-12 reveal">
                        <span className="section-label">Blog</span>
                        <h1 className="font-display text-3xl sm:text-4xl md:text-5xl font-bold mt-3 text-neutral-900 dark:text-white">
                            {l.pageTitle}
                        </h1>
                        <p className="mt-4 text-base text-neutral-500 dark:text-neutral-400 max-w-2xl mx-auto">
                            {l.subtitle}
                        </p>
                    </div>

                    {items.length === 0 ? (
                        <p className="text-center text-neutral-500 dark:text-neutral-400">{l.empty}</p>
                    ) : (
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 reveal">
                            {items.map((article) => (
                                <article key={article.slug} className="rounded-2xl border border-neutral-100 dark:border-neutral-800 bg-white dark:bg-neutral-900 p-6 flex flex-col">
                                    {article.category && (
                                        <span className="inline-block mb-2 self-start px-2.5 py-1 rounded-md text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 uppercase tracking-wide">
                                            {article.category}
                                        </span>
                                    )}
                                    <h2 className="font-display text-lg font-bold text-neutral-900 dark:text-white mb-2">
                                        {article.title}
                                    </h2>
                                    {article.excerpt && (
                                        <p className="text-sm text-neutral-600 dark:text-neutral-400 mb-4 flex-1">{article.excerpt}</p>
                                    )}
                                    <Link href={`/blog/${article.slug}`} className="text-brand-500 font-medium text-sm hover:underline mt-auto">
                                        {l.readMore}
                                    </Link>
                                </article>
                            ))}
                        </div>
                    )}

                    {links.length > 3 && (
                        <nav className="mt-12 flex items-center justify-center gap-2" aria-label="Pagination">
                            {links.map((link, i) => (
                                link.url ? (
                                    <Link
                                        key={i}
                                        href={link.url}
                                        preserveScroll
                                        className={`px-3 py-1.5 rounded-lg text-sm ${link.active ? 'bg-brand-500 text-white' : 'text-neutral-600 dark:text-neutral-400 hover:bg-neutral-100 dark:hover:bg-neutral-800'}`}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ) : (
                                    <span key={i} className="px-3 py-1.5 text-sm text-neutral-300 dark:text-neutral-700" dangerouslySetInnerHTML={{ __html: link.label }} />
                                )
                            ))}
                        </nav>
                    )}
                </div>
            </section>
        </MarketingLayout>
    );
}
