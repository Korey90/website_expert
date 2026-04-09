import { usePage } from '@inertiajs/react';

const DEFAULTS = {
    title:    { en: 'Ready to Get Started?',                                                          pl: 'Gotowy, aby zacząć?' },
    subtitle: { en: 'Get a free, no-obligation quote in 48 hours. No jargon. No pressure. Just honest advice.', pl: 'Bezpłatna wycena bez zobowiązań w 48 godziny. Bez żargonu. Bez nacisków. Tylko uczciwe porady.' },
    button_text: { en: 'Get My Free Quote',     pl: 'Chcę bezpłatną wycenę' },
    button_url:  '#calculator',
    extra: {
        secondary_button_text_en: 'Book a Discovery Call',
        secondary_button_text_pl: 'Umów rozmowę',
        secondary_button_url:     '/contact',
    },
};

export default function CtaBanner({ data }) {
    const { locale = 'en' } = usePage().props;

    const t = (obj, key) =>
        obj?.[`${key}_${locale}`] ?? obj?.[`${key}_en`] ?? obj?.[key] ?? '';

    const d     = data ?? {};
    const extra = d.extra ?? DEFAULTS.extra;

    const title              = d.title       || DEFAULTS.title[locale]       || DEFAULTS.title.en;
    const subtitle           = d.subtitle    || DEFAULTS.subtitle[locale]    || DEFAULTS.subtitle.en;
    const primaryText        = d.button_text || DEFAULTS.button_text[locale] || DEFAULTS.button_text.en;
    const primaryUrl         = d.button_url  || DEFAULTS.button_url;
    const secondaryText      = t(extra, 'secondary_button_text') || t(DEFAULTS.extra, 'secondary_button_text');
    const secondaryUrl       = extra.secondary_button_url || DEFAULTS.extra.secondary_button_url;

    return (
        <section id="cta-main" className="py-16 bg-neutral-50 dark:bg-neutral-950 relative overflow-hidden border-t border-neutral-200 dark:border-transparent">
            {/* Subtle brand ambient */}
            <div className="absolute inset-0 bg-linear-to-br from-brand-500/5 via-transparent to-transparent dark:from-brand-500/10" aria-hidden="true" />
            <div className="absolute top-0 left-1/2 -translate-x-1/2 w-150 h-40 bg-brand-500/10 blur-3xl rounded-full" aria-hidden="true" />

            <div className="relative mx-auto max-w-5xl px-4 sm:px-6 lg:px-8 text-center reveal">
                <h2 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white mb-4">
                    {title}
                </h2>
                <p className="text-neutral-600 dark:text-neutral-400 text-lg max-w-2xl mx-auto mb-8">
                    {subtitle}
                </p>
                <div className="flex flex-col sm:flex-row gap-3 justify-center">
                    <a
                        href={primaryUrl}
                        className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl bg-brand-500 text-white font-bold text-base hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/25"
                    >
                        {primaryText}
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                    </a>
                    {secondaryText && secondaryUrl && (
                        <a
                            href={secondaryUrl}
                            className="inline-flex items-center justify-center gap-2 px-8 py-4 rounded-xl border border-neutral-300 text-neutral-700 font-bold text-base hover:border-brand-500 hover:text-brand-500 active:scale-95 transition-all dark:border-neutral-700 dark:text-neutral-300"
                        >
                            {secondaryText}
                        </a>
                    )}
                </div>
            </div>
        </section>
    );
}

