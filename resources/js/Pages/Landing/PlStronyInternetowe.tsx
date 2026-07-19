import { useState, useEffect, useRef } from 'react';
import { Head, router } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import { pushEvent } from '@/utils/dataLayer';

// ─── Helpers ─────────────────────────────────────────────────────────────────

function getQueryParam(name: string): string {
    if (typeof window === 'undefined') return '';
    return new URLSearchParams(window.location.search).get(name) ?? '';
}

// ─── Sub-components ───────────────────────────────────────────────────────────

function TrustItem({ icon, text }: { icon: string; text: string }) {
    return (
        <div className="flex items-center gap-2 text-sm text-neutral-600 dark:text-white/70">
            <span className="text-brand-500 font-bold">{icon}</span>
            <span>{text}</span>
        </div>
    );
}

function ProblemCard({ icon, title, body }: { icon: string; title: string; body: string }) {
    return (
        <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 p-6">
            <div className="text-3xl mb-3">{icon}</div>
            <h3 className="font-bold text-neutral-900 dark:text-white mb-2">{title}</h3>
            <p className="text-neutral-600 dark:text-white/70 text-sm leading-relaxed">{body}</p>
        </div>
    );
}

function ProcessStep({ num, title, body }: { num: number; title: string; body: string }) {
    return (
        <div className="flex gap-4">
            <div className="flex-shrink-0 w-10 h-10 rounded-full bg-brand-500 text-white flex items-center justify-center font-bold text-sm">
                {num}
            </div>
            <div>
                <h3 className="font-bold text-neutral-900 dark:text-white mb-1">{title}</h3>
                <p className="text-neutral-600 dark:text-white/70 text-sm leading-relaxed">{body}</p>
            </div>
        </div>
    );
}

function FaqItem({ q, a }: { q: string; a: string }) {
    const [open, setOpen] = useState(false);
    return (
        <div className="border-b border-neutral-200 dark:border-white/10 py-4">
            <button
                className="flex w-full items-center justify-between text-left gap-4"
                onClick={() => setOpen(!open)}
                aria-expanded={open}
            >
                <span className="font-semibold text-neutral-900 dark:text-white">{q}</span>
                <span className="flex-shrink-0 text-brand-500 font-bold text-lg">{open ? '−' : '+'}</span>
            </button>
            {open && (
                <p className="mt-3 text-neutral-600 dark:text-white/70 text-sm leading-relaxed">{a}</p>
            )}
        </div>
    );
}

// ─── Contact Form ─────────────────────────────────────────────────────────────

interface FormData {
    name: string;
    email: string;
    phone: string;
    company: string;
    current_website: string;
    message: string;
    audience_type: string;
    gdpr_consent: boolean;
    utm_source: string;
    utm_medium: string;
    utm_campaign: string;
    utm_term: string;
    gclid: string;
    gbraid: string;
    wbraid: string;
}

interface FormErrors {
    [key: string]: string;
}

function ContactForm({ formRef }: { formRef: React.RefObject<HTMLElement | null> }) {
    const [form, setForm] = useState<FormData>({
        name: '', email: '', phone: '', company: '', current_website: '',
        message: '', audience_type: '', gdpr_consent: false,
        utm_source: '', utm_medium: '', utm_campaign: '', utm_term: '',
        gclid: '', gbraid: '', wbraid: '',
    });
    const [errors, setErrors] = useState<FormErrors>({});
    const [status, setStatus] = useState<'idle' | 'submitting' | 'success' | 'error'>('idle');
    const [touched, setTouched] = useState(false);

    // Collect tracking params on mount
    useEffect(() => {
        setForm(prev => ({
            ...prev,
            utm_source:   getQueryParam('utm_source'),
            utm_medium:   getQueryParam('utm_medium'),
            utm_campaign: getQueryParam('utm_campaign'),
            utm_term:     getQueryParam('utm_term'),
            gclid:        getQueryParam('gclid'),
            gbraid:       getQueryParam('gbraid'),
            wbraid:       getQueryParam('wbraid'),
        }));
        pushEvent('landing_page_view', { landing_page: 'strony-internetowe-uk', locale: 'pl' });
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
        const { name, value, type } = e.target;
        const val = type === 'checkbox' ? (e.target as HTMLInputElement).checked : value;
        setForm(prev => ({ ...prev, [name]: val }));
        if (errors[name]) setErrors(prev => { const n = { ...prev }; delete n[name]; return n; });
        if (!touched) {
            setTouched(true);
            pushEvent('form_start', { landing_page: 'strony-internetowe-uk', locale: 'pl' });
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (status === 'submitting') return;

        setStatus('submitting');
        setErrors({});

        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
            const res = await fetch('/pl/strony-internetowe-uk/wyslij', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json',
                },
                body: JSON.stringify(form),
            });

            const data = await res.json();

            if (!res.ok) {
                if (res.status === 422 && data.errors) {
                    setErrors(data.errors);
                    setStatus('idle');
                    return;
                }
                throw new Error('Server error');
            }

            // Fire GTM lead_created AFTER successful DB save
            pushEvent('lead_created', {
                lead_id:      data.lead_id ?? undefined,
                lead_type:    'website',
                landing_page: 'strony-internetowe-uk',
                locale:       'pl',
                currency:     'GBP',
            });

            setStatus('success');
        } catch {
            setStatus('error');
        }
    };

    if (status === 'success') {
        return (
            <div role="alert" aria-live="polite" className="rounded-2xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 p-8 text-center">
                <div className="text-4xl mb-4">✅</div>
                <h3 className="text-xl font-bold text-neutral-900 dark:text-white mb-3">
                    Dziękujemy — zapytanie zostało wysłane!
                </h3>
                <p className="text-neutral-600 dark:text-white/70 mb-4">
                    Zapoznam się z Twoim zapytaniem i odpiszę w ciągu jednego dnia roboczego.
                </p>
                <p className="text-sm text-neutral-500 dark:text-white/50">
                    W pilnych sprawach możesz zadzwonić lub napisać bezpośrednio.
                </p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} noValidate className="space-y-5" aria-label="Formularz wyceny strony internetowej">

            {/* Name */}
            <div>
                <label htmlFor="name" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Imię i nazwisko <span className="text-brand-500">*</span>
                </label>
                <input
                    id="name" name="name" type="text" autoComplete="name"
                    value={form.name} onChange={handleChange} required
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? 'err-name' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.name ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="Jan Kowalski"
                />
                {errors.name && <p id="err-name" role="alert" className="mt-1 text-xs text-red-500">{errors.name}</p>}
            </div>

            {/* Email */}
            <div>
                <label htmlFor="email" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Adres e-mail <span className="text-brand-500">*</span>
                </label>
                <input
                    id="email" name="email" type="email" autoComplete="email"
                    value={form.email} onChange={handleChange} required
                    aria-invalid={!!errors.email}
                    aria-describedby={errors.email ? 'err-email' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.email ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="jan@twojafirma.co.uk"
                />
                {errors.email && <p id="err-email" role="alert" className="mt-1 text-xs text-red-500">{errors.email}</p>}
            </div>

            {/* Phone */}
            <div>
                <label htmlFor="phone" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Numer telefonu
                </label>
                <input
                    id="phone" name="phone" type="tel" autoComplete="tel"
                    value={form.phone} onChange={handleChange}
                    className="w-full rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="+44 7700 000000"
                />
            </div>

            {/* Company */}
            <div>
                <label htmlFor="company" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Nazwa firmy
                </label>
                <input
                    id="company" name="company" type="text" autoComplete="organization"
                    value={form.company} onChange={handleChange}
                    className="w-full rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="Kowalski Budownictwo Ltd"
                />
            </div>

            {/* Current website */}
            <div>
                <label htmlFor="current_website" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Obecna strona internetowa
                </label>
                <input
                    id="current_website" name="current_website" type="url"
                    value={form.current_website} onChange={handleChange}
                    aria-invalid={!!errors.current_website}
                    aria-describedby={errors.current_website ? 'err-current-website' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.current_website ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="https://twojafirma.co.uk (opcjonalnie)"
                />
                {errors.current_website && <p id="err-current-website" role="alert" className="mt-1 text-xs text-red-500">{errors.current_website}</p>}
            </div>

            {/* Message */}
            <div>
                <label htmlFor="message" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Krótko opisz, czego potrzebujesz <span className="text-brand-500">*</span>
                </label>
                <textarea
                    id="message" name="message" rows={4}
                    value={form.message} onChange={handleChange} required
                    aria-invalid={!!errors.message}
                    aria-describedby={errors.message ? 'err-message' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none ${errors.message ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="Np. Prowadzę firmę remontową w Manchesterze, mam starą stronę, chciałbym ją odświeżyć..."
                />
                {errors.message && <p id="err-message" role="alert" className="mt-1 text-xs text-red-500">{errors.message}</p>}
            </div>

            {/* Audience qualifier */}
            <div>
                <label htmlFor="audience_type" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Do jakich klientów kierujesz swoje usługi?
                </label>
                <select
                    id="audience_type" name="audience_type"
                    value={form.audience_type} onChange={handleChange}
                    className="w-full rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                >
                    <option value="">— wybierz opcję (opcjonalnie) —</option>
                    <option value="english_clients">Głównie klientów anglojęzycznych</option>
                    <option value="polish_clients">Głównie Polaków w UK</option>
                    <option value="both">Do obu grup</option>
                    <option value="unknown">Jeszcze nie wiem</option>
                </select>
            </div>

            {/* GDPR */}
            <div className="flex items-start gap-3">
                <input
                    id="gdpr_consent" name="gdpr_consent" type="checkbox"
                    checked={form.gdpr_consent}
                    onChange={handleChange}
                    className="mt-1 h-4 w-4 flex-shrink-0 rounded border-neutral-300 text-brand-500 focus:ring-brand-500"
                />
                <label htmlFor="gdpr_consent" className="text-xs text-neutral-500 dark:text-white/60 leading-relaxed">
                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu przygotowania wyceny i nawiązania kontaktu.
                    Dane nie będą udostępniane osobom trzecim. Mogę wycofać zgodę w dowolnym momencie.{' '}
                    <a href="/privacy-policy" target="_blank" className="underline hover:text-brand-500">Polityka prywatności</a>
                </label>
            </div>
            {errors.gdpr_consent && <p id="err-gdpr" role="alert" className="text-xs text-red-500">{errors.gdpr_consent}</p>}

            {status === 'error' && (
                <p role="alert" className="text-sm text-red-500 text-center">
                    Coś poszło nie tak. Spróbuj ponownie lub napisz bezpośrednio na adres e-mail.
                </p>
            )}

            <button
                type="submit"
                disabled={status === 'submitting'}
                onClick={() => pushEvent('primary_cta_click', { element: 'form_submit', landing_page: 'strony-internetowe-uk' })}
                className="w-full rounded-xl bg-brand-500 hover:bg-brand-600 disabled:opacity-60 text-white font-bold py-4 text-base transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
            >
                {status === 'submitting' ? 'Wysyłanie…' : 'Otrzymaj bezpłatną wycenę →'}
            </button>

            <p className="text-center text-xs text-neutral-400 dark:text-white/40">
                Bez zobowiązań i bez presji sprzedażowej. Odpowiedź w ciągu jednego dnia roboczego.
            </p>
        </form>
    );
}

// ─── Sticky Mobile CTA ────────────────────────────────────────────────────────

function StickyMobileCta({ onQuoteClick }: { onQuoteClick: () => void }) {
    const [hidden, setHidden] = useState(false);
    const lastScroll = useRef(0);

    useEffect(() => {
        const handler = () => {
            const current = window.scrollY;
            // Hide when scrolling down, show when scrolling up
            setHidden(current > lastScroll.current && current > 200);
            lastScroll.current = current;
        };
        window.addEventListener('scroll', handler, { passive: true });
        return () => window.removeEventListener('scroll', handler);
    }, []);

    return (
        <div
            className={`fixed bottom-0 inset-x-0 z-40 md:hidden bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-white/10 safe-area-pb transition-transform duration-300 ${hidden ? 'translate-y-full' : 'translate-y-0'}`}
            style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
        >
            <div className="flex gap-2 px-4 py-3">
                <button
                    onClick={() => {
                        pushEvent('primary_cta_click', { element: 'sticky_cta', landing_page: 'strony-internetowe-uk' });
                        onQuoteClick();
                    }}
                    className="flex-1 rounded-xl bg-brand-500 text-white font-bold py-3 text-sm"
                >
                    Bezpłatna wycena
                </button>
                <a
                    href={`tel:${getQueryParam('phone') || ''}`}
                    onClick={() => pushEvent('phone_click', { landing_page: 'strony-internetowe-uk' })}
                    className="flex-shrink-0 rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm font-bold text-neutral-700 dark:text-white"
                >
                    Zadzwoń
                </a>
            </div>
        </div>
    );
}

// ─── Main Page ─────────────────────────────────────────────────────────────────

interface CmsExtra {
    hero_price_label?: string;
    hero_delivery_label?: string;
    hero_cta_secondary?: string;
    trust_items?: Array<{ icon: string; text: string }>;
    package_from?: string;
    package_scope?: string[];
    package_excluded?: string[];
    no_hidden_fees_note?: string;
    founder_name?: string;
    founder_bio?: string;
    founder_photo_url?: string;
    founder_linkedin_url?: string;
    founder_location?: string;
    founder_experience?: string;
    testimonials?: Array<{ quote: string; name: string; role: string; photo_url?: string; verify_url?: string }>;
    faq_items?: Array<{ q: string; a: string }>;
    form_title?: string;
    form_note?: string;
    contact_phone?: string;
    success_heading?: string;
    success_body?: string;
}

interface Props {
    footer?: { extra?: Record<string, unknown> } | null;
    cms?: { title?: string; subtitle?: string; extra?: CmsExtra } | null;
}

export default function PlStronyInternetowe({ footer, cms }: Props) {
    const formRef = useRef<HTMLElement | null>(null);

    // CMS helper — returns CMS value if set, otherwise fallback
    const c = <T,>(key: keyof CmsExtra, fallback: T): T =>
        (cms?.extra?.[key] as T | undefined) ?? fallback;

    const heroTitle     = cms?.title || 'Strony internetowe dla polskich firm w Wielkiej Brytanii';
    const heroSubtitle  = cms?.subtitle || 'Stworzę profesjonalną stronę, która wzbudza zaufanie brytyjskich klientów i pomaga zdobywać nowe zapytania. Cały projekt omawiamy po polsku.';
    const heroPriceLabel    = c('hero_price_label', 'Strony od £799');
    const heroDeliveryLabel = c('hero_delivery_label', 'Realizacja 3–5 tygodni');
    const heroCtaSecondary  = c('hero_cta_secondary', 'Zobacz realizacje');
    const trustItems        = c('trust_items', [
        { icon: '📍', text: 'Baza w Północnej Irlandii' },
        { icon: '🇵🇱', text: 'Obsługa po polsku' },
        { icon: '📱', text: 'Mobile-first' },
        { icon: '💷', text: 'Ustalona cena z góry' },
    ]);
    const packageFrom       = c('package_from', '£799');
    const packageScope      = c('package_scope', [
        'Do pięciu głównych podstron', 'Projekt dopasowany do branży',
        'Mobile-first (telefony i tablety)', 'Formularz kontaktowy',
        'Podstawowe SEO techniczne', 'Google Analytics i Search Console',
        'SSL i zabezpieczenie formularzy', 'Panel do samodzielnej edycji treści',
        'Dwie rundy poprawek', 'Wdrożenie i szkolenie po polsku',
    ]);
    const packageExcluded   = c('package_excluded', [
        'Rozbudowany sklep e-commerce', 'Niestandardowe aplikacje',
        'Profesjonalna sesja zdjęciowa', 'Nieograniczone poprawki', 'Miesięczne SEO',
    ]);
    const noHiddenFees      = c('no_hidden_fees_note', 'Brak ukrytych kosztów. Ostateczny zakres i cena są ustalane przed rozpoczęciem prac.');
    const founderBio        = c('founder_bio', 'Website Expert to pracownia założona przez developera z siedzibą w Północnej Irlandii. Rozmawiasz bezpośrednio z osobą odpowiedzialną za planowanie, projektowanie, budowanie i uruchamianie Twojego projektu.');
    const founderName       = c('founder_name', '');
    const founderPhoto      = c('founder_photo_url', '');
    const founderLinkedin   = c('founder_linkedin_url', '');
    const founderLocation   = c('founder_location', 'Północna Irlandia');
    const founderExp        = c('founder_experience', '10+ lat doświadczenia');
    const faqItems          = c('faq_items', [
        { q: 'Ile kosztuje strona internetowa dla firmy?', a: 'Podstawowy pakiet (do 5 podstron, formularz kontaktowy, SEO techniczne, CMS) zaczyna się od £799. Ostateczna cena zależy od zakresu projektu i jest ustalana przed rozpoczęciem prac.' },
        { q: 'Jak długo trwa realizacja projektu?', a: 'Typowy czas realizacji podstawowego pakietu to 3–5 tygodni od potwierdzenia zakresu i pierwszej wpłaty.' },
        { q: 'Co muszę przygotować ze swojej strony?', a: 'Podstawowe informacje o firmie, opis usług, kontakt, kilka zdjęć. Pomagam w zbieraniu treści podczas procesu.' },
        { q: 'Czy będę właścicielem strony i domeny?', a: 'Tak. Po zakończeniu projektu i opłaceniu faktury otrzymujesz pełny dostęp do strony. Domenę rejestruj zawsze na siebie lub swoją firmę.' },
        { q: 'Czy będę mógł samodzielnie aktualizować treści?', a: 'Tak. Strona jest wyposażona w panel CMS umożliwiający edycję treści bez znajomości programowania. Szkolenie wchodzi w cenę.' },
        { q: 'Co się dzieje po uruchomieniu strony?', a: 'Przeprowadzam szkolenie z panelu administracyjnego i pozostaję dostępny w przypadku pytań technicznych.' },
    ]);
    const formTitle         = c('form_title', 'Omów projekt i otrzymaj bezpłatną wycenę');
    const formNote          = c('form_note', 'Bez zobowiązań i bez presji sprzedażowej. Odpowiedź w ciągu jednego dnia roboczego.');
    const contactPhone      = c('contact_phone', '');
    const successHeading    = c('success_heading', 'Dziękujemy — zapytanie zostało wysłane!');
    const successBody       = c('success_body', 'Zapoznam się z Twoim zapytaniem i odpiszę w ciągu jednego dnia roboczego.');

    const scrollToForm = () => {
        formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        pushEvent('primary_cta_click', { element: 'hero_cta', landing_page: 'strony-internetowe-uk' });
    };

    const scrollToPortfolio = () => {
        document.getElementById('portfolio')?.scrollIntoView({ behavior: 'smooth' });
    };

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>Strony internetowe dla polskich firm w UK — Website Expert</title>
                <meta name="description" content="Profesjonalna strona internetowa dla polskiej firmy działającej w Wielkiej Brytanii. Obsługa po polsku, projekt pod rynek brytyjski. Od £799. Bez ukrytych kosztów." />
                <link rel="canonical" href="https://website-expert.uk/pl/strony-internetowe-uk" />
                <meta property="og:title" content="Strony internetowe dla polskich firm w UK" />
                <meta property="og:description" content="Obsługa po polsku, strona pod rynek brytyjski. Od £799." />
                <meta property="og:locale" content="pl_PL" />
                <link rel="alternate" hrefLang="pl" href="https://website-expert.uk/pl/strony-internetowe-uk" />
                <link rel="alternate" hrefLang="en" href="https://website-expert.uk/en/web-design-northern-ireland" />
                <link rel="alternate" hrefLang="x-default" href="https://website-expert.uk/en/web-design-northern-ireland" />

            </Head>

            {/* ── SEKCJA 1: HERO ─────────────────────────────────────────────── */}
            <section className="relative bg-gradient-to-br from-neutral-950 via-neutral-900 to-neutral-800 text-white pt-20 pb-16 md:pt-28 md:pb-24">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-3xl">
                        {/* Badge */}
                        <div className="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-4 py-1.5 text-xs font-medium text-white/80 mb-6">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                            Obsługa po polsku · Projekt pod rynek brytyjski
                        </div>

                        {/* H1 */}
                        <h1 className="font-display text-4xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight mb-6">
                            {heroTitle.includes('polskich') ? (
                                <>Strony internetowe dla{' '}<span className="text-brand-400">polskich firm</span>{' '}w Wielkiej Brytanii</>
                            ) : heroTitle}
                        </h1>

                        <p className="text-lg text-white/80 leading-relaxed mb-8 max-w-2xl">
                            {heroSubtitle}
                        </p>

                        {/* Price strip */}
                        <div className="flex flex-wrap gap-3 mb-8 text-sm font-medium text-white/70">
                            <span dangerouslySetInnerHTML={{ __html: heroPriceLabel.replace('£', '<strong class="text-white">£').replace(/(\d+)/, '$1</strong>') }} />
                            <span className="text-white/30">·</span>
                            <span>{heroDeliveryLabel}</span>
                            <span className="text-white/30">·</span>
                            <span>Bez ukrytych kosztów</span>
                        </div>

                        {/* CTAs */}
                        <div className="flex flex-col sm:flex-row gap-3">
                            <button
                                onClick={scrollToForm}
                                className="inline-flex items-center justify-center rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-8 py-4 text-base transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-neutral-900"
                            >
                                Otrzymaj bezpłatną wycenę
                            </button>
                            <button
                                onClick={scrollToPortfolio}
                                className="inline-flex items-center justify-center rounded-2xl border border-white/30 hover:border-white/60 text-white font-bold px-8 py-4 text-base transition-colors"
                            >
                                {heroCtaSecondary}
                            </button>
                        </div>

                        {/* Trust strip */}
                        <div className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
                            {trustItems.map((item, i) => (
                                <TrustItem key={i} icon={item.icon} text={item.text} />
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 2: POLSKA OBSŁUGA, BRYTYJSKI STANDARD ─────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Polska obsługa.{' '}
                            <span className="text-brand-500">Brytyjski standard</span> strony.
                        </h2>
                        <p className="mt-4 text-lg text-neutral-600 dark:text-white/70 max-w-2xl mx-auto">
                            Możemy omówić cały projekt po polsku, ale strona będzie przygotowana dla klientów
                            działających na rynku brytyjskim.
                        </p>
                    </div>
                    <div className="grid sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        {[
                            { icon: '🗣️', title: 'Rozmowa po polsku', body: 'Bez nieporozumień dotyczących technologii, umowy, zakresu i kosztów projektu.' },
                            { icon: '🇬🇧', title: 'Dopasowanie do rynku UK', body: 'Waluta, lokalizacja, SEO, Google Business Profile i brytyjska terminologia.' },
                            { icon: '📝', title: 'Treści po angielsku', body: 'Strona może być angielska albo dwujęzyczna, zależnie od Twoich klientów.' },
                            { icon: '🤝', title: 'Bezpośrednia współpraca', body: 'Rozmawiasz ze mną, a nie z handlowcem przekazującym informacje dalej.' },
                        ].map(item => (
                            <ProblemCard key={item.title} icon={item.icon} title={item.title} body={item.body} />
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 3: DLA KOGO ────────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                        <div>
                            <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white mb-4">
                                Dla polskich przedsiębiorców, którzy chcą{' '}
                                <span className="text-brand-500">rozwijać firmę w UK</span>
                            </h2>
                            <p className="text-neutral-600 dark:text-white/70 mb-6 leading-relaxed">
                                Strona może pozyskiwać klientów anglojęzycznych, polskojęzycznych albo obie grupy jednocześnie.
                            </p>
                            <button onClick={scrollToForm} className="rounded-xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-6 py-3 text-sm transition-colors">
                                Omów projekt po polsku →
                            </button>
                        </div>
                        <div className="mt-8 lg:mt-0 grid grid-cols-2 gap-3">
                            {[
                                'Firmy budowlane i remontowe', 'Hydraulicy i elektrycy',
                                'Gabinety kosmetyczne', 'Kliniki i dentyści',
                                'Firmy sprzątające', 'Firmy transportowe',
                                'Mechanicy samochodowi', 'Instruktorzy i szkolenia',
                                'Restauracje i catering', 'Księgowi i doradcy',
                            ].map(item => (
                                <div key={item} className="flex items-center gap-2 text-sm text-neutral-700 dark:text-white/80">
                                    <span className="w-1.5 h-1.5 rounded-full bg-brand-500 flex-shrink-0" />
                                    {item}
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 4: PAKIET OD £799 ─────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Strona firmowa od <span className="text-brand-500">{packageFrom}</span>
                        </h2>
                        <p className="mt-4 text-neutral-600 dark:text-white/70">
                            Ustalony zakres. Ustalona cena. Bez niespodzianek.
                        </p>
                    </div>

                    <div className="lg:grid lg:grid-cols-2 gap-8">
                        {/* Included */}
                        <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-neutral-900 p-6 mb-6 lg:mb-0">
                            <h3 className="font-bold text-neutral-900 dark:text-white mb-4">Co jest w cenie</h3>
                            <ul className="space-y-2.5">
                                {[
                                    'Do pięciu głównych podstron',
                                    'Projekt dopasowany do branży',
                                    'Mobile-first (telefony i tablety)',
                                    'Formularz kontaktowy',
                                    'Podstawowe SEO techniczne',
                                    'Google Analytics i Search Console',
                                    'SSL i zabezpieczenie formularzy',
                                    'Panel do samodzielnej edycji treści',
                                    'Dwie rundy poprawek',
                                    'Wdrożenie i szkolenie po polsku',
                                ].map(item => (
                                    <li key={item} className="flex items-start gap-2 text-sm text-neutral-700 dark:text-white/80">
                                        <span className="text-green-500 font-bold flex-shrink-0 mt-0.5">✓</span>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        {/* Not included + guarantees */}
                        <div className="space-y-6">
                            <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-neutral-50 dark:bg-neutral-900 p-6">
                                <h3 className="font-bold text-neutral-900 dark:text-white mb-4">Czego nie obejmuje pakiet podstawowy</h3>
                                <ul className="space-y-2">
                                    {packageExcluded.map((item, i) => (
                                        <li key={i} className="flex items-start gap-2 text-sm text-neutral-500 dark:text-white/50">
                                            <span className="flex-shrink-0 mt-0.5">—</span>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>

                            <div className="rounded-2xl bg-brand-500/10 border border-brand-500/30 p-6">
                                <p className="font-bold text-neutral-900 dark:text-white mb-1">Brak ukrytych kosztów</p>
                                <p className="text-sm text-neutral-600 dark:text-white/70">
                                    {noHiddenFees}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 text-center">
                        <button onClick={scrollToForm} className="rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-8 py-4 text-base transition-colors">
                            Zapytaj o wycenę →
                        </button>
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 5: PORTFOLIO ──────────────────────────────────────── */}
            <section id="portfolio" className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        Przykładowe realizacje
                    </h2>
                    <div className="grid md:grid-cols-3 gap-6">
                        {[
                            {
                                industry: 'Gabinet dentystyczny · UK',
                                title: 'Klinika stomatologiczna',
                                challenge: 'Stara strona utrudniała rezerwację wizyt i nie prezentowała usług w zrozumiały sposób.',
                                solution: 'Redesign mobile-first, integracja rezerwacji online, opinie Google.',
                                result: 'Więcej zapytań o nowych pacjentów przez formularz kontaktowy.',
                            },
                            {
                                industry: 'Firma budowlana · Belfast',
                                title: 'Wykonawca remontów',
                                challenge: 'Brak strony internetowej — klienci nie mogli sprawdzić firmy online przed kontaktem.',
                                solution: 'Nowa strona z portfolio realizacji, formularzem wyceny i opiniami klientów.',
                                result: 'Stała obecność online i regularne zapytania z wyszukiwarki.',
                            },
                            {
                                industry: 'Kancelaria · Londyn',
                                title: 'Kancelaria prawna',
                                challenge: 'Strona nie budowała zaufania i nie odpowiadała na pytania potencjalnych klientów.',
                                solution: 'Czytelna prezentacja usług, biografii i procesu współpracy.',
                                result: 'Wyraźny wzrost liczby zapytań przez formularz kontaktowy.',
                            },
                        ].map(item => (
                            <div key={item.title} className="rounded-2xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 p-6">
                                <p className="text-xs font-medium text-brand-500 uppercase tracking-widest mb-2">{item.industry}</p>
                                <h3 className="font-bold text-neutral-900 dark:text-white mb-4">{item.title}</h3>
                                <div className="space-y-3 text-sm">
                                    <div>
                                        <p className="font-semibold text-neutral-500 dark:text-white/50 text-xs uppercase tracking-wide mb-1">Problem</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.challenge}</p>
                                    </div>
                                    <div>
                                        <p className="font-semibold text-neutral-500 dark:text-white/50 text-xs uppercase tracking-wide mb-1">Rozwiązanie</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.solution}</p>
                                    </div>
                                    <div className="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700/40 p-3">
                                        <p className="font-semibold text-green-700 dark:text-green-400 text-xs uppercase tracking-wide mb-1">Efekt</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.result}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 6: STRONA PL CZY EN ──────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        W jakim języku powinna być Twoja strona?
                    </h2>
                    <div className="grid md:grid-cols-3 gap-6 mb-8">
                        {[
                            {
                                lang: '🇬🇧 Angielska',
                                desc: 'Dla firm, które kierują usługi głównie do klientów brytyjskich i chcą dotrzeć do jak największej liczby odbiorców.',
                                rec: false,
                            },
                            {
                                lang: '🇵🇱 Polska',
                                desc: 'Dla firm działających przede wszystkim wśród Polonii w UK.',
                                rec: false,
                            },
                            {
                                lang: '🇬🇧🇵🇱 Dwujęzyczna',
                                desc: 'Dla firm obsługujących oba segmenty — polskojęzyczny i anglojęzyczny.',
                                rec: true,
                            },
                        ].map(item => (
                            <div key={item.lang} className={`rounded-2xl border p-5 ${item.rec ? 'border-brand-500 bg-brand-500/5' : 'border-neutral-200 dark:border-white/10'}`}>
                                {item.rec && <span className="inline-block text-xs font-bold text-brand-500 uppercase tracking-widest mb-2">Najczęściej polecam</span>}
                                <h3 className="font-bold text-neutral-900 dark:text-white mb-2">{item.lang}</h3>
                                <p className="text-sm text-neutral-600 dark:text-white/70">{item.desc}</p>
                            </div>
                        ))}
                    </div>
                    <div className="rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 p-6 text-center">
                        <p className="text-neutral-700 dark:text-white/80 text-sm leading-relaxed">
                            <strong>Moja rekomendacja:</strong> W większości przypadków firm lokalnych działających w UK polecam
                            stronę angielską albo dwujęzyczną. Sam polski język może ograniczyć liczbę potencjalnych klientów.
                            Omówimy to razem podczas rozmowy.
                        </p>
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 7: O ZAŁOŻYCIELU ──────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="lg:grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            {/* Placeholder lub zdjęcie z CMS */}
                            {founderPhoto ? (
                                <img src={founderPhoto} alt={founderName || 'Założyciel'} className="w-48 h-48 rounded-2xl object-cover mb-6" />
                            ) : (
                                <div className="w-48 h-48 rounded-2xl bg-neutral-200 dark:bg-neutral-700 mb-6 flex items-center justify-center text-neutral-400 dark:text-neutral-500 text-sm">
                                    Zdjęcie<br/>założyciela
                                </div>
                            )}
                            <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white mb-4">
                                Porozmawiasz bezpośrednio z osobą, która tworzy Twoją stronę
                            </h2>
                            <p className="text-neutral-600 dark:text-white/70 leading-relaxed mb-4">
                                {founderBio}
                            </p>
                            <p className="text-neutral-600 dark:text-white/70 leading-relaxed mb-6">
                                Bez account managerów. Bez outsourcowanej komunikacji. Bez niejasnej odpowiedzialności.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">{founderExp}</span>
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">{founderLocation}</span>
                                {founderLinkedin && (
                                    <a href={founderLinkedin} target="_blank" rel="noopener noreferrer" className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-brand-500 hover:underline">LinkedIn</a>
                                )}
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">Obsługa PL</span>
                            </div>
                        </div>
                        <div className="mt-8 lg:mt-0 space-y-4">
                            <h3 className="font-bold text-neutral-900 dark:text-white mb-4">Jasne zasady od początku</h3>
                            {[
                                ['📄', 'Pisemny zakres projektu przed startem'],
                                ['💷', 'Ustalona cena — bez zaskakujących dopłat'],
                                ['📅', 'Ustalony harmonogram realizacji'],
                                ['🔄', 'Określona liczba poprawek w cenie'],
                                ['🌐', 'Domena zarejestrowana na Ciebie lub Twoją firmę'],
                                ['🔑', 'Pełny dostęp do strony po zakończeniu projektu'],
                                ['🧾', 'Faktura i pełna dokumentacja projektu'],
                                ['🤝', 'Brak przymusowej umowy na długoterminowe utrzymanie'],
                            ].map(([icon, text]) => (
                                <div key={text as string} className="flex items-start gap-3">
                                    <span className="text-xl flex-shrink-0">{icon}</span>
                                    <p className="text-sm text-neutral-700 dark:text-white/80 leading-relaxed">{text}</p>
                                </div>
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 8: PROCES ─────────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        Jak wygląda współpraca
                    </h2>
                    <div className="grid sm:grid-cols-2 gap-8 mb-8">
                        <ProcessStep num={1} title="Rozmowa" body="Omawiamy Twoją firmę, klientów, cele i potrzebne funkcje strony." />
                        <ProcessStep num={2} title="Wycena" body="Otrzymujesz pisemny zakres projektu, ustaloną cenę i realny harmonogram." />
                        <ProcessStep num={3} title="Projekt i budowa" body="Projektuję, buduję i testuję stronę z regularnymi aktualizacjami postępów." />
                        <ProcessStep num={4} title="Uruchomienie i wsparcie" body="Uruchamiam stronę, przeprowadzam szkolenie i pozostaję dostępny po wdrożeniu." />
                    </div>
                    <p className="text-center text-sm text-neutral-500 dark:text-white/50">
                        Typowy czas realizacji podstawowego pakietu: <strong className="text-neutral-700 dark:text-white/80">3–5 tygodni</strong>
                    </p>
                </div>
            </section>

            {/* ── SEKCJA 9: FAQ ─────────────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        Najczęstsze pytania
                    </h2>
                    <div>
                        {faqItems.map((item, i) => (
                            <FaqItem key={i} q={item.q} a={item.a} />
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SEKCJA 10: FORMULARZ ─────────────────────────────────────── */}
            <section
                id="contact-form"
                ref={formRef as React.RefObject<HTMLElement>}
                className="py-16 md:py-24 bg-white dark:bg-neutral-950"
            >
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-8">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            {formTitle.includes('bezpłatną') ? (
                                <>Omów projekt i otrzymaj{' '}<span className="text-brand-500">bezpłatną wycenę</span></>
                            ) : formTitle}
                        </h2>
                        <p className="mt-4 text-neutral-600 dark:text-white/70">
                            {formNote}
                        </p>
                    </div>
                    <div className="rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 p-6 sm:p-8">
                        <ContactForm formRef={formRef} />
                    </div>
                </div>
            </section>

            {/* Sticky mobile CTA */}
            <StickyMobileCta onQuoteClick={scrollToForm} />

            {/* Mobile spacer so sticky bar doesn't overlap last section */}
            <div className="h-16 md:hidden" />
        </MarketingLayout>
    );
}
