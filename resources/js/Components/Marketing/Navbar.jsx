import { useState, useEffect, useRef } from 'react';
import { usePage, router } from '@inertiajs/react';

// Flag emoji map – add more as needed
const FLAG = { en: '🇬🇧', pl: '🇵🇱', de: '🇩🇪', fr: '🇫🇷', es: '🇪🇸', pt: '🇵🇹', uk: '🇺🇦' };

const LINK_DEFAULTS = [
    { href: '#about',      label_en: 'About Us',        label_pl: 'O nas' },
    { href: '#services',   label_en: 'Services',        label_pl: 'Oferta' },
    { href: '#portfolio',  label_en: 'Portfolio',       label_pl: 'Portfolio' },
    { href: '#calculate',  label_en: 'Cost Calculator', label_pl: 'Kalkulator' },
    { href: '#contact',    label_en: 'Contact',         label_pl: 'Kontakt' },
];

export default function Navbar({ auth, data = null }) {
    const page = usePage();
    const { locale, available_locales } = page.props;
    const isHome = page.url === '/' || page.url.startsWith('/?');
    const resolveHref = (href) => href.startsWith('#') && !isHome ? '/' + href : href;

    const extra = data?.extra ?? {};
    const nt    = (key, fb = '') => extra[`${key}_${locale}`] ?? extra[`${key}_en`] ?? fb;

    const rawLinks = Array.isArray(extra.links) && extra.links.length > 0 ? extra.links : LINK_DEFAULTS;
    const navLinks = rawLinks.map(l => ({
        href:  resolveHref(l.href),
        label: l[`label_${locale}`] ?? l.label_en ?? l.href,
    }));
    const ctaText = nt('cta_text', locale === 'pl' ? 'Bezpłatna wycena' : 'Free Quote');
    const ctaHref = resolveHref(extra.cta_href || '#contact');

    const langOptions = Object.entries(available_locales ?? {}).map(([code, label]) => ({
        code,
        label: label.replace(/\s*\p{Emoji_Presentation}+$/u, '').trim(),
        flag: FLAG[code] ?? '🌐',
    }));

    const [mobileOpen, setMobileOpen] = useState(false);
    const [scrolled, setScrolled]     = useState(false);
    const [dark, setDark]             = useState(() => (localStorage.getItem('theme') || 'dark') === 'dark');
    const [langOpen, setLangOpen]     = useState(false);
    const langRef    = useRef(null);
    const headerRef  = useRef(null);
    const mobileOpenRef = useRef(false);

    useEffect(() => { mobileOpenRef.current = mobileOpen; }, [mobileOpen]);

    const switchLang = (code) => {
        setLangOpen(false);
        router.visit(`/lang/${code}`, { preserveScroll: false });
    };

    // Apply dark class on mount and toggle
    useEffect(() => {
        document.documentElement.classList.toggle('dark', dark);
        localStorage.setItem('theme', dark ? 'dark' : 'light');
    }, [dark]);

    // Navbar shadow on scroll + close mobile menu on scroll
    useEffect(() => {
        const onScroll = () => {
            setScrolled(window.scrollY > 20);
            if (mobileOpenRef.current) setMobileOpen(false);
        };
        window.addEventListener('scroll', onScroll, { passive: true });
        return () => window.removeEventListener('scroll', onScroll);
    }, []);

    // Close lang dropdown + mobile menu on outside click
    useEffect(() => {
        const handler = (e) => {
            if (langRef.current && !langRef.current.contains(e.target)) setLangOpen(false);
            if (headerRef.current && !headerRef.current.contains(e.target)) setMobileOpen(false);
        };
        document.addEventListener('mousedown', handler);
        document.addEventListener('touchstart', handler);
        return () => {
            document.removeEventListener('mousedown', handler);
            document.removeEventListener('touchstart', handler);
        };
    }, []);

    const currentLang = langOptions.find(l => l.code === locale) ?? langOptions[0];

    return (
        <header
            id="navbar"
            ref={headerRef}
            className={`fixed top-0 inset-x-0 z-50 transition-all duration-300 ${
                scrolled || mobileOpen
                    ? 'bg-white/90 dark:bg-neutral-950/90 backdrop-blur-sm shadow-sm'
                    : 'bg-transparent'
            }`}
        >
            <nav className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex items-center justify-between h-16 md:h-20">

                {/* Logo */}
                <a href={resolveHref('/#hero')} className="flex items-center gap-2 shrink-0 group" aria-label="Website Expert – strona główna">
                    <svg width="36" height="36" viewBox="0 0 36 36" fill="none" className="shrink-0" aria-hidden="true">
                        <rect width="36" height="36" rx="8" className="fill-brand-500" />
                        <path d="M9 12L18 8L27 12V18C27 23.1 22.8 27.7 18 29C13.2 27.7 9 23.1 9 18V12Z" fill="white" opacity="0.2" />
                        <path d="M13 18L16.5 21.5L23 14.5" stroke="white" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" />
                    </svg>
                    <span className="font-display font-bold text-xl tracking-tight text-neutral-900 dark:text-white group-hover:text-brand-500 transition-colors">
                        Website<span className="text-brand-500">Expert</span>
                    </span>
                </a>

                {/* Desktop menu */}
                <ul className="hidden md:flex items-center gap-8 text-sm font-medium text-neutral-600 dark:text-neutral-300">
                    {navLinks.map(l => (
                        <li key={l.href}>
                            <a href={l.href} className="hover:text-brand-500 transition-colors">{l.label}</a>
                        </li>
                    ))}
                </ul>

                {/* Right controls */}
                <div className="flex items-center gap-1.5 sm:gap-3 shrink-0">

                    {/* Language switcher */}
                    <div className="relative" ref={langRef}>
                        <button
                            onClick={() => setLangOpen(o => !o)}
                            aria-haspopup="listbox"
                            aria-expanded={langOpen}
                            aria-label="Zmień język"
                            className="flex items-center gap-1.5 px-2 py-1.5 rounded-lg text-sm font-semibold text-neutral-600 dark:text-neutral-300 hover:text-brand-500 dark:hover:text-brand-400 hover:bg-neutral-100 dark:hover:bg-neutral-800 transition-colors"
                        >
                            <span aria-hidden="true">{currentLang.flag}</span>
                            <span className="hidden sm:inline">{currentLang.code.toUpperCase()}</span>
                            <svg className="w-3.5 h-3.5 opacity-50" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>
                        {langOpen && (
                            <ul
                                role="listbox"
                                aria-label="Dostępne języki"
                                className="absolute right-0 mt-1 w-36 rounded-xl border border-neutral-100 dark:border-neutral-700 bg-white dark:bg-neutral-900 shadow-lg py-1 z-50 text-sm font-medium"
                            >
                                {langOptions.map(opt => (
                                    <li
                                        key={opt.code}
                                        role="option"
                                        aria-selected={locale === opt.code}
                                        onClick={() => switchLang(opt.code)}
                                        className={`flex items-center gap-2.5 px-3 py-2 cursor-pointer transition-colors ${
                                            locale === opt.code
                                                ? 'text-brand-500 bg-brand-50 dark:bg-brand-500/10'
                                                : 'text-neutral-700 dark:text-neutral-300 hover:text-brand-500 hover:bg-neutral-50 dark:hover:bg-neutral-800'
                                        }`}
                                    >
                                        <span aria-hidden="true">{opt.flag}</span> {opt.label}
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>

                    {/* Dark mode toggle */}
                    <button
                        onClick={() => setDark(d => !d)}
                        aria-label="Przełącz tryb ciemny/jasny"
                        className="p-2 rounded-lg text-neutral-500 dark:text-neutral-400 hover:text-brand-500 dark:hover:text-brand-400 transition-colors"
                    >
                        {dark ? (
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <circle cx="12" cy="12" r="5" /><line x1="12" y1="1" x2="12" y2="3" /><line x1="12" y1="21" x2="12" y2="23" />
                                <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" /><line x1="18.36" y1="18.36" x2="19.78" y2="19.78" />
                                <line x1="1" y1="12" x2="3" y2="12" /><line x1="21" y1="12" x2="23" y2="12" />
                                <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" /><line x1="18.36" y1="5.64" x2="19.78" y2="4.22" />
                            </svg>
                        ) : (
                            <svg className="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" />
                            </svg>
                        )}
                    </button>

                    {/* Client Portal */}
                    <a
                        href="/portal"
                        className="hidden sm:inline-flex items-center gap-1.5 px-3 py-2 rounded-lg border border-neutral-300 dark:border-neutral-700 text-sm font-semibold text-neutral-600 dark:text-neutral-200 hover:border-neutral-500 hover:text-neutral-700 dark:hover:text-neutral-50 transition-colors"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        {locale === 'pl' ? 'Portal Klienta' : locale === 'pt' ? 'Portal do Cliente' : 'Client Portal'}
                    </a>

                    {/* CTA button */}
                    <a
                        href={ctaHref}
                        className="hidden sm:inline-flex items-center gap-1.5 px-4 py-2 rounded-lg shadow-brand-400/25 hover:shadow-brand-400/50 shadow-md text-brand-400 border border-brand-400 text-sm font-semibold hover:bg-brand-400 hover:text-neutral-100 active:scale-95 transition-all"
                    >
                        {ctaText}
                    </a>

                    {/* Mobile hamburger */}
                    <button
                        onClick={() => setMobileOpen(o => !o)}
                        aria-label="Menu mobilne"
                        aria-expanded={mobileOpen}
                        aria-controls="mobile-menu"
                        className="md:hidden p-2 rounded-lg text-neutral-600 dark:text-neutral-300 hover:text-brand-500 transition-colors"
                    >
                        {mobileOpen ? (
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        ) : (
                            <svg className="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                            </svg>
                        )}
                    </button>
                </div>
            </nav>

            {/* Mobile menu */}
            <div
                id="mobile-menu"
                aria-hidden={!mobileOpen}
                className={`md:hidden overflow-hidden transition-all duration-300 ease-in-out border-neutral-100 dark:border-neutral-800 ${
                    mobileOpen ? 'max-h-150 opacity-100 border-t' : 'max-h-0 opacity-0'
                }`}
            >
                <div className="px-4 pb-5">
                    <ul className="flex flex-col gap-1 pt-3 text-sm font-medium text-neutral-700 dark:text-neutral-300">
                        {navLinks.map(l => (
                            <li key={l.href}>
                                <a href={l.href} onClick={() => setMobileOpen(false)} className="block py-2.5 hover:text-brand-500 transition-colors">
                                    {l.label}
                                </a>
                            </li>
                        ))}

                        {/* Language row */}
                        <li className="border-t border-neutral-100 dark:border-neutral-800 pt-3 mt-1">
                            <p className="text-xs text-neutral-400 uppercase tracking-widest mb-2">Język / Language</p>
                            <div className="flex gap-2">
                                {langOptions.map(opt => (
                                    <button
                                        key={opt.code}
                                        onClick={() => switchLang(opt.code)}
                                        className={`flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg border text-sm transition-colors ${
                                            locale === opt.code
                                                ? 'border-brand-500 text-brand-500 bg-brand-50 dark:bg-brand-500/10 font-semibold'
                                                : 'border-neutral-200 dark:border-neutral-700 text-neutral-600 dark:text-neutral-300 font-medium hover:text-brand-500 hover:border-brand-500'
                                        }`}
                                    >
                                        {opt.flag} {opt.code.toUpperCase()}
                                    </button>
                                ))}
                            </div>
                        </li>

                        <li className="pt-2">
                            <a
                                href="/portal"
                                onClick={() => setMobileOpen(false)}
                                className="block text-center px-4 py-3 rounded-lg border border-neutral-200 dark:border-neutral-700 text-neutral-700 dark:text-neutral-200 font-semibold hover:text-brand-500 hover:border-brand-500 transition-colors"
                            >
                                {locale === 'pl' ? '🔒 Portal Klienta' : locale === 'pt' ? '🔒 Portal do Cliente' : '🔒 Client Portal'}
                            </a>
                        </li>
                        <li className="pt-2">
                            <a
                                href={ctaHref}
                                onClick={() => setMobileOpen(false)}
                                className="block text-center px-4 py-3 rounded-lg bg-brand-500 text-white font-semibold hover:bg-brand-600 transition-colors"
                            >
                                {ctaText}
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </header>
    );
}
