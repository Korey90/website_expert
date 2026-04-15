import { useState, useEffect, useCallback } from 'react';
import { usePage } from '@inertiajs/react';

const AVATAR_COLORS = [
    'bg-brand-500', 'bg-neutral-600', 'bg-brand-700',
    'bg-neutral-700', 'bg-brand-600', 'bg-neutral-500',
];

const DEFAULTS = {
    title:         { en: 'Trusted by UK Businesses',                    pl: 'Zaufali nam przedsiębiorcy z całego UK' },
    section_label: { en: 'Trusted By',                                  pl: 'Zaufali nam' },
    clients: [
        { name: 'Hargreaves Solicitors' }, { name: 'NTS Direct' }, { name: 'Oakfield Dental' },
        { name: 'Pinnacle Recruitment' },  { name: 'Coastal Escapes' },  { name: 'Bloom & Grow' },
    ],
    reviews: [
        { name: 'Robert Hargreaves', company: 'Hargreaves Solicitors', rating: 5,
          text_en: 'WebsiteExpert delivered exactly what we needed — a clean, professional site that our clients trust. Delivered on time, on budget. Highly recommended.',
          text_pl: 'WebsiteExpert dostarczył dokładnie to, czego potrzebowaliśmy — czystą, profesjonalną stronę, której nasi klienci ufają. Na czas i w budżecie. Gorąco polecam.' },
        { name: 'Lisa Thornton', company: 'NTS Direct', rating: 5,
          text_en: 'Our new e-commerce platform has transformed our business. The B2B trade portal alone saved our sales team hours every week.',
          text_pl: 'Nasza nowa platforma e-commerce całkowicie przekształciła nasz biznes. Sam portal handlowy oszczędził naszemu zespołowi sprzedaży godziny tygodniowo.' },
        { name: 'Dr Priya Patel', company: 'Oakfield Dental', rating: 5,
          text_en: 'From the initial call to launch, everything was smooth and professional. Our new website has already brought us several new patients.',
          text_pl: 'Od pierwszej rozmowy do uruchomienia wszystko przebiegało sprawnie i profesjonalnie. Nasza nowa strona już przyciągnęła kilku nowych pacjentów.' },
        { name: 'Daniel Walsh', company: 'Pinnacle Recruitment', rating: 5,
          text_en: "We were referred to WebsiteExpert by another client and we're so glad we made the call. The quality has been exceptional throughout.",
          text_pl: 'Zostaliśmy poleceni do WebsiteExpert przez innego klienta i bardzo cieszymy się, że zadzwoniliśmy. Jakość przez cały czas jest wyjątkowa.' },
    ],
};

function getInitials(name) {
    return name.split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
}

function Stars({ count }) {
    return (
        <div className="flex gap-0.5" aria-label={`${count} stars`}>
            {Array.from({ length: 5 }, (_, i) => (
                <svg key={i} className={`w-4 h-4 ${i < count ? 'text-yellow-400' : 'text-neutral-300 dark:text-neutral-700'}`} fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z" />
                </svg>
            ))}
        </div>
    );
}

export default function TrustStrip({ data, testimonials }) {
    const { locale = 'en' } = usePage().props;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d      = data        ?? {};
    const extra  = d.extra     ?? {};
    const tExtra = testimonials?.extra ?? {};

    const title        = d.title        || DEFAULTS.title[locale]         || DEFAULTS.title.en;
    const sectionLabel = t(extra, 'section_label') || DEFAULTS.section_label[locale] || DEFAULTS.section_label.en;

    // clients: [{name}] or strings
    const rawClients = extra.clients ?? DEFAULTS.clients;
    const clients    = rawClients.map(c => (typeof c === 'string' ? c : c.name));

    // reviews from testimonials section
    const reviews = tExtra.reviews ?? DEFAULTS.reviews;

    const [current, setCurrent]   = useState(0);
    const [isPaused, setIsPaused] = useState(false);
    const total = reviews.length;
    const goTo  = useCallback((i) => setCurrent(((i % total) + total) % total), [total]);

    useEffect(() => {
        if (isPaused || total === 0) return;
        const id = setInterval(() => goTo(current + 1), 5000);
        return () => clearInterval(id);
    }, [current, isPaused, goTo, total]);

    return (
        <section id="zaufali" className="py-16 sm:py-20 md:py-28 bg-neutral-50 dark:bg-neutral-800 border-t border-neutral-200 dark:border-neutral-700">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="text-center mb-10 sm:mb-14 reveal">
                    <span className="section-label">{sectionLabel}</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                </div>

                {/* Client logo strip */}
                {clients.length > 0 && (
                    <div className="flex flex-wrap justify-center items-center gap-3 sm:gap-6 md:gap-10 mb-10 sm:mb-16 reveal">
                        {clients.map((name, i) => (
                            <div key={i} className="h-9 w-28 sm:h-10 sm:w-32 rounded-lg bg-neutral-200 dark:bg-neutral-700 flex items-center justify-center text-xs text-neutral-500 dark:text-neutral-400 font-medium px-2 text-center">
                                {name}
                            </div>
                        ))}
                    </div>
                )}

                {/* Testimonial carousel */}
                {reviews.length > 0 && (
                    <div
                        className="max-w-2xl mx-auto"
                        tabIndex={0}
                        role="region"
                        aria-label="Client testimonials carousel"
                        onMouseEnter={() => setIsPaused(true)}
                        onMouseLeave={() => setIsPaused(false)}
                        onKeyDown={(e) => {
                            if (e.key === 'ArrowLeft')  goTo(current - 1);
                            if (e.key === 'ArrowRight') goTo(current + 1);
                        }}
                    >
                        <div className="overflow-hidden" aria-live="polite">
                            <div
                                className="flex transition-transform duration-500 ease-in-out"
                                style={{ transform: `translateX(-${current * 100}%)` }}
                            >
                                {reviews.map((item, idx) => (
                                    <div
                                        key={idx}
                                        className="w-full shrink-0 px-2"
                                        aria-hidden={idx !== current ? 'true' : undefined}
                                    >
                                        <article
                                            className="bg-white dark:bg-neutral-950 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-5 sm:p-8 md:p-10 shadow-sm"
                                            aria-label={`Review from ${item.name}`}
                                        >
                                            <div className="mb-3">
                                                <Stars count={item.rating ?? 5} />
                                            </div>
                                            <blockquote className="text-neutral-700 dark:text-neutral-300 text-sm sm:text-base md:text-lg leading-relaxed mb-5 sm:mb-6">
                                                <p>"{t(item, 'text') || item.text_en || ''}"</p>
                                            </blockquote>
                                            <div className="flex items-center gap-3">
                                                <div
                                                    className={`w-10 h-10 rounded-full ${AVATAR_COLORS[idx % AVATAR_COLORS.length]} flex items-center justify-center text-white text-sm font-bold shrink-0`}
                                                    aria-hidden="true"
                                                >
                                                    {getInitials(item.name ?? '??')}
                                                </div>
                                                <div>
                                                    <p className="font-semibold text-neutral-900 dark:text-white text-sm">{item.name}</p>
                                                    <p className="text-xs text-neutral-500 dark:text-neutral-400">{item.company}</p>
                                                </div>
                                            </div>
                                        </article>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Navigation: prev · dots · next */}
                        <div className="flex items-center justify-center gap-4 mt-6">
                            <button
                                type="button"
                                onClick={() => goTo(current - 1)}
                                aria-label="Previous testimonial"
                                className="w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 hover:border-brand-500 hover:text-brand-500 transition-colors"
                            >
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                                </svg>
                            </button>

                            <div className="flex gap-1.5" role="tablist">
                                {reviews.map((_, i) => (
                                    <button
                                        key={i}
                                        role="tab"
                                        aria-selected={i === current}
                                        aria-label={`Review ${i + 1}`}
                                        onClick={() => goTo(i)}
                                        className={`h-2 rounded-full transition-all duration-300 ml-1 ${i === current ? 'bg-brand-500 w-5' : 'w-2 bg-neutral-300 dark:bg-neutral-600'}`}
                                    />
                                ))}
                            </div>

                            <button
                                type="button"
                                onClick={() => goTo(current + 1)}
                                aria-label="Next testimonial"
                                className="w-9 h-9 rounded-full border border-neutral-200 dark:border-neutral-700 flex items-center justify-center text-neutral-500 hover:border-brand-500 hover:text-brand-500 transition-colors"
                            >
                                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2} aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </section>
    );
}

