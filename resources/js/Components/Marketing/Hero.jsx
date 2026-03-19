const DEFAULT_TITLE_LINE1 = 'Twoja strona.';
const DEFAULT_TITLE_LINE2 = 'Twój wzrost.';
const DEFAULT_TITLE_LINE3 = 'Nasz kod.';
const DEFAULT_SUBTITLE =
    'Tworzymy strony i aplikacje internetowe dla małych i\u00a0średnich firm. ' +
    'Od wizytówki po zaawansowany sklep \u2013 dostarczamy gotowe do zarabiania rozwiązania.';
const DEFAULT_BADGE    = 'Nowe projekty \u2013 od 2\u00a0tygodni';
const DEFAULT_BTN1_TEXT = 'Sprawdź koszt projektu';
const DEFAULT_BTN1_URL  = '#kalkulator';
const DEFAULT_BTN2_TEXT = 'Zobacz nasze realizacje';
const DEFAULT_BTN2_URL  = '#portfolio';

export default function Hero({ section }) {
    const title       = section?.title    ?? null;
    const subtitle    = section?.subtitle ?? DEFAULT_SUBTITLE;
    const extra       = section?.extra    ?? {};

    const badgeText   = extra.badge_text           ?? DEFAULT_BADGE;
    const btn1Text    = section?.button_text        ?? DEFAULT_BTN1_TEXT;
    const btn1Url     = section?.button_url         ?? DEFAULT_BTN1_URL;
    const btn2Text    = extra.secondary_button_text ?? DEFAULT_BTN2_TEXT;
    const btn2Url     = extra.secondary_button_url  ?? DEFAULT_BTN2_URL;

    // Parse title into up to 3 lines split by newline, falling back to hardcoded Polish lines
    let titleLines;
    if (title) {
        const parts = title.split(/\n/);
        titleLines = [parts[0] ?? '', parts[1] ?? '', parts[2] ?? ''];
    } else {
        titleLines = [DEFAULT_TITLE_LINE1, DEFAULT_TITLE_LINE2, DEFAULT_TITLE_LINE3];
    }

    return (
        <section id="hero" className="relative min-h-screen flex items-center overflow-hidden pt-16 md:pt-20">
            {/* Background */}
            <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
            <div className="absolute top-1/4 right-0 w-96 h-96 bg-brand-500/10 rounded-full blur-3xl pointer-events-none" aria-hidden="true" />
            <div className="absolute bottom-0 left-0 w-64 h-64 bg-brand-500/5 rounded-full blur-2xl pointer-events-none" aria-hidden="true" />

            <div className="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-20 md:py-28 grid lg:grid-cols-2 gap-12 lg:gap-16 items-center">
                {/* Text */}
                <div>
                    <span className="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 mb-6">
                        <span className="w-1.5 h-1.5 rounded-full bg-brand-500 animate-pulse" />
                        {badgeText}
                    </span>
                    <h1 className="font-display text-4xl sm:text-5xl lg:text-6xl font-extrabold leading-tight tracking-tight text-neutral-900 dark:text-white">
                        {titleLines[0]}{titleLines[0] && <br />}
                        {titleLines[1] && <><span className="text-brand-500">{titleLines[1]}</span><br /></>}
                        {titleLines[2]}
                    </h1>
                    <p className="mt-6 text-lg text-neutral-600 dark:text-neutral-400 max-w-xl leading-relaxed">
                        {subtitle}
                    </p>
                    <div className="mt-8 flex flex-col sm:flex-row gap-3">
                        <a
                            href={btn1Url}
                            className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold text-base hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/25"
                        >
                            {btn1Text}
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                            </svg>
                        </a>
                        <a
                            href={btn2Url}
                            className="inline-flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-transparent border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-300 font-semibold text-base hover:border-brand-500 hover:text-brand-500 active:scale-95 transition-all"
                        >
                            {btn2Text}
                        </a>
                    </div>

                    {/* Social proof */}
                    <div className="mt-10 flex items-center gap-4">
                        <div className="flex -space-x-2">
                            {[['AK','brand'], ['MB','neutral'], ['PW','brand']].map(([init, color]) => (
                                <div key={init} className={`w-9 h-9 rounded-full bg-${color}-200 dark:bg-${color}-900 border-2 border-white dark:border-neutral-950 flex items-center justify-center text-xs font-bold text-${color}-700 dark:text-${color}-300`}>
                                    {init}
                                </div>
                            ))}
                        </div>
                        <p className="text-sm text-neutral-500 dark:text-neutral-400">
                            <span className="font-semibold text-neutral-800 dark:text-neutral-200">+50 firm</span> już nam zaufało
                        </p>
                    </div>
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
                                <div className="ml-3 flex-1 h-5 rounded bg-neutral-200 dark:bg-neutral-700 flex items-center px-2">
                                    <span className="text-xs text-neutral-400 dark:text-neutral-500">twojafirma.pl</span>
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
                                <p className="text-xs text-neutral-500 dark:text-neutral-400">Czas realizacji</p>
                                <p className="text-sm font-bold text-neutral-800 dark:text-white">od 2 tygodni</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Scroll indicator */}
            <a
                href="#o-nas"
                className="absolute bottom-8 left-1/2 -translate-x-1/2 flex flex-col items-center gap-1 animate-bounce text-neutral-400 hover:text-brand-500 transition-colors"
                aria-label="Przewiń do sekcji O nas"
            >
                <span className="text-xs">Przewiń</span>
                <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </a>
        </section>
    );
}
