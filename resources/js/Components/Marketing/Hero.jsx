import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    title:       'Twoja strona.\nTwoj wzrost.\nNasz kod.',
    subtitle:    'Tworzymy strony i aplikacje internetowe dla malych i srednich firm. Od wizytowki po zaawansowany sklep.',
    button_text: 'Sprawdz koszt projektu',
    button_url:  '#calculate',
    extra: {
        badge_text_en:             '5-star rated on Google',
        badge_text_pl:             'Oceny 5 gwiazdek w Google',
        badge_text_pt:             'Avaliações 5 estrelas no Google',
        secondary_button_text_en:  'View Our Work',
        secondary_button_text_pl:  'Zobacz nasze realizacje',
        secondary_button_text_pt:  'Veja nossos trabalhos',
        secondary_button_url:      '#portfolio',
        scroll_label_en:           'Scroll',
        scroll_label_pl:           'Przewiń',
        scroll_label_pt:           'Role',
        mockup_domain_en:          'www.yourcompany.co.uk',
        mockup_domain_pl:          'www.twojafirma.pl',
        mockup_domain_pt:          'www.suaempresa.com.br',
        mockup_timeline_label_en:  'Delivery time',
        mockup_timeline_label_pl:  'Czas realizacji',
        mockup_timeline_label_pt:  'Prazo de entrega',
        mockup_timeline_value_en:  'from 2 weeks',
        mockup_timeline_value_pl:  'od 2 tygodni',
        mockup_timeline_value_pt:  'a partir de 2 semanas',
        stats: [],
    },
};

export default function Hero({ data }) {
    const { locale = 'en' } = usePage().props;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d     = data ?? DEFAULTS;
    const extra = d.extra ?? DEFAULTS.extra;

    const badgeText          = t(extra, 'badge_text');
    const secondaryBtnText   = t(extra, 'secondary_button_text');
    const secondaryBtnUrl    = extra.secondary_button_url ?? '#portfolio';
    const scrollLabel        = t(extra, 'scroll_label') || 'Scroll';
    const mockupDomain       = t(extra, 'mockup_domain') || 'yourcompany.co.uk';
    const mockupTimelineLabel = t(extra, 'mockup_timeline_label') || 'Delivery time';
    const mockupTimelineValue = t(extra, 'mockup_timeline_value') || 'from 2 weeks';

    // Title may contain \n for line breaks
    const titleLines = (d.title ?? DEFAULTS.title).split('\n');

    return (
        <section id="hero" className="relative min-h-screen flex items-center overflow-hidden pt-4 md:pt-10">
            {/* Background */}
            <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
            <div className="absolute top-1/4 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none hidden md:block" aria-hidden="true" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-brand-500/5 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />

            <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 md:py-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                {/* Text */}
                <div>
                    {badgeText && (
                        <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 mb-6">
                            <span className="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse" />
                            {badgeText}
                        </span>
                    )}

                    <h1 className="font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight text-neutral-900 dark:text-white">
                        {titleLines.map((line, i) => (
                            <span key={i}>
                                {i === 1
                                    ? <span className="text-brand-500">{line}</span>
                                    : line}
                                {i < titleLines.length - 1 && <br />}
                            </span>
                        ))}
                    </h1>

                    {d.subtitle && (
                        <p className="mt-6 text-lg text-neutral-600 dark:text-neutral-400 max-w-xl leading-relaxed">
                            {d.subtitle}
                        </p>
                    )}

                    <div className="mt-8 flex flex-col sm:flex-row gap-3">
                        <a
                            href={d.button_url ?? '#calculate'}
                            className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl border border-brand-400 text-brand-400 font-semibold text-base hover:text-white hover:bg-brand-400 active:scale-95 transition-all duration-150 shadow-lg shadow-brand-400/25"
                        >
                            {d.button_text ?? DEFAULTS.button_text}
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                        {secondaryBtnText && (
                            <a
                                href={secondaryBtnUrl}
                                className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-transparent border border-neutral-300 dark:border-neutral-700 text-neutral-500 dark:text-neutral-300 font-semibold text-base hover:border-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-50 active:scale-95 transition-all"
                            >
                                {secondaryBtnText}
                            </a>
                        )}
                    </div>

                    {/* Stats from backend */}
                    {extra.stats && extra.stats.length > 0 && (
                        <div className="mt-10 flex flex-wrap gap-8">
                            {extra.stats.map((stat, i) => (
                                <div key={i}>
                                    <p className="text-2xl font-extrabold text-neutral-900 dark:text-white">{stat.value}</p>
                                    <p className="text-xs text-neutral-500 dark:text-neutral-400 mt-0.5">{t(stat, 'label')}</p>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* Browser mockup */}
                <div className="relative hidden lg:flex items-center justify-center">
                    <div className="relative w-full max-w-md">
                        <div className="rounded-2xl overflow-hidden shadow-2xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-800">
                            {/* Browser chrome */}
                            <div className="flex items-center gap-1.5 px-4 py-3 bg-neutral-100 dark:bg-neutral-900 border-b border-neutral-200 dark:border-neutral-700">
                                <span className="w-3 h-3 rounded-full bg-red-400" />
                                <span className="w-3 h-3 rounded-full bg-yellow-400" />
                                <span className="w-3 h-3 rounded-full bg-green-400" />
                                <div className="ml-3 flex-1 h-6 rounded bg-neutral-200 dark:bg-neutral-700 flex items-center px-2 pb-1">
                                    <span className="text-xs text-neutral-500 dark:text-neutral-300">{mockupDomain}</span>
                                </div>
                            </div>
                            {/* Mock content */}
                            <div className="p-5 space-y-3">
                                <div className="h-4 w-3/4 rounded bg-brand-500/20" />
                                <div className="h-3 w-full rounded bg-neutral-100 dark:bg-neutral-700" />
                                <div className="h-3 w-5/6 rounded bg-neutral-100 dark:bg-neutral-700" />
                                <div className="h-8 w-32 rounded-lg bg-brand-500/80 mt-4" />
                                <div className="grid grid-cols-3 gap-2 mt-4">
                                    <div className="h-16 rounded-lg bg-neutral-100 dark:bg-neutral-700" />
                                    <div className="h-16 rounded-lg bg-neutral-100 dark:bg-neutral-700" />
                                    <div className="h-16 rounded-lg bg-neutral-100 dark:bg-neutral-700" />
                                </div>
                            </div>
                        </div>
                        {/* Floating badge */}
                        <div className="absolute -bottom-4 -right-4 bg-white dark:bg-neutral-800 border border-neutral-100 dark:border-neutral-700 rounded-xl px-4 py-2.5 shadow-xl flex items-center gap-2">
                            <span className="text-2xl" aria-hidden="true">🚀</span>
                            <div>
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">{mockupTimelineLabel}</p>
                                <p className="text-sm font-bold text-neutral-800 dark:text-white">{mockupTimelineValue}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Scroll indicator */}
            <a
                href="#about"
                className="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 animate-bounce text-neutral-400 hover:text-brand-500 transition-colors"
                aria-label={scrollLabel}
            >
                <span className="text-xs">{scrollLabel}</span>
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </a>
        </section>
    );
}
