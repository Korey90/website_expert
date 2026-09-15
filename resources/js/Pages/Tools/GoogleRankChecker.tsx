import { useState } from 'react';
import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useRankCheckerTrans from '@/Hooks/useRankCheckerTrans';
import GoogleRankCheckerForm from '@/Components/Tools/GoogleRankCheckerForm';
import QuickContactForm from '@/Components/Contact/QuickContactForm';

interface ContactLabels {
    ctaFormTitle: string;
    labelName: string;
    labelContact: string;
    labelMessage: string;
    labelWebsite: string;
    placeholderName: string;
    placeholderContact: string;
    placeholderWebsite: string;
    gdprText: string;
    gdprLink: string;
    submitBtn: string;
    sending: string;
    successTitle: string;
    successMsg: string;
    errorMsg: string;
    validationMsg: string;
    clearMessage: string;
}

interface FaqEntry {
    q: string;
    a: string;
}

interface GoogleRankCheckerPageProps {
    footer: { extra: Record<string, unknown> } | null;
    locations: string[];
    defaults: {
        location: string;
        device: 'desktop' | 'mobile';
    };
}

function FaqItem({ q, a }: FaqEntry) {
    const [open, setOpen] = useState(false);
    return (
        <div className="border-b border-neutral-200 dark:border-white/10 py-4">
            <button
                type="button"
                className="flex w-full items-center justify-between text-left gap-4"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
            >
                <span className="font-semibold text-neutral-900 dark:text-white">{q}</span>
                <span className="flex-shrink-0 text-brand-500 font-bold text-lg">{open ? '\u2212' : '+'}</span>
            </button>
            {open && <p className="mt-3 text-neutral-600 dark:text-white/70 text-sm leading-relaxed">{a}</p>}
        </div>
    );
}

export default function GoogleRankChecker({ footer, locations, defaults }: GoogleRankCheckerPageProps) {
    const t = useRankCheckerTrans();
    const { rank_checker_translations, locale } = usePage().props as {
        rank_checker_translations?: { faq?: FaqEntry[]; contact?: ContactLabels };
        locale?: string;
    };
    const faqItems: FaqEntry[] = rank_checker_translations?.faq ?? [];
    const contactLabels = rank_checker_translations?.contact;

    const [showQuoteForm, setShowQuoteForm] = useState(false);
    const [checkedWebsite, setCheckedWebsite] = useState('');

    function handleCtaAction(domain: string) {
        setCheckedWebsite(domain);
        setShowQuoteForm(true);
    }

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>{t('meta.title')}</title>
                <meta name="description" content={t('meta.description')} />
                <link rel="canonical" href="https://website-expert.uk/tools/google-rank-checker" />
                <meta property="og:title" content={t('meta.title')} />
                <meta property="og:description" content={t('meta.description')} />
            </Head>

            <section className="relative bg-gradient-to-br from-neutral-950 via-neutral-900 to-neutral-800 text-white pt-20 pb-16 md:pt-24 md:pb-20">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 text-center">
                    <h1 className="text-3xl sm:text-4xl font-bold mb-4">{t('h1')}</h1>
                    <p className="text-white/70 text-lg">{t('intro')}</p>
                </div>
            </section>

            <section className="py-12 md:py-16">
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    {!showQuoteForm || !contactLabels ? (
                        <GoogleRankCheckerForm
                            locations={locations}
                            defaultLocation={defaults.location}
                            defaultDevice={defaults.device}
                            onCtaAction={handleCtaAction}
                        />
                    ) : (
                        <QuickContactForm
                            l={contactLabels}
                            locale={locale ?? 'en'}
                            serviceTitle="SEO"
                            serviceSlug="seo"
                            initialWebsite={checkedWebsite}
                        />
                    )}
                </div>
            </section>

            {faqItems.length > 0 && (
                <section className="py-12 md:py-16 bg-neutral-50 dark:bg-neutral-900">
                    <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                        <h2 className="text-2xl font-bold text-neutral-900 dark:text-white mb-4">FAQ</h2>
                        {faqItems.map((item) => (
                            <FaqItem key={item.q} q={item.q} a={item.a} />
                        ))}
                    </div>
                </section>
            )}
        </MarketingLayout>
    );
}
