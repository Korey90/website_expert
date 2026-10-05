import { useState, useEffect, useRef } from 'react';
import { Head } from '@inertiajs/react';
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
        <div className="flex items-center gap-2 text-sm text-white/70">
            <span className="text-brand-400 font-bold">{icon}</span>
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
            <p className={`mt-3 text-neutral-600 dark:text-white/70 text-sm leading-relaxed ${open ? 'block' : 'hidden'}`}>{a}</p>
        </div>
    );
}

// ─── Contact Form ─────────────────────────────────────────────────────────────

interface FormData {
    name: string;
    email: string;
    phone: string;
    current_website: string;
    message: string;
    gdpr_consent: boolean;
    utm_source: string;
    utm_medium: string;
    utm_campaign: string;
    utm_term: string;
    gclid: string;
    gbraid: string;
    wbraid: string;
}

interface FormErrors { [key: string]: string }

function ContactForm({ formRef }: { formRef: React.RefObject<HTMLElement | null> }) {
    const [form, setForm] = useState<FormData>({
        name: '', email: '', phone: '', current_website: '', message: '',
        gdpr_consent: false,
        utm_source: '', utm_medium: '', utm_campaign: '', utm_term: '',
        gclid: '', gbraid: '', wbraid: '',
    });
    const [errors, setErrors] = useState<FormErrors>({});
    const [status, setStatus] = useState<'idle' | 'submitting' | 'success' | 'error'>('idle');
    const [touched, setTouched] = useState(false);

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
        pushEvent('landing_page_view', { landing_page: 'web-design-northern-ireland', locale: 'en' });
    }, []);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        const { name, value, type } = e.target;
        const val = type === 'checkbox' ? (e.target as HTMLInputElement).checked : value;
        setForm(prev => ({ ...prev, [name]: val }));
        if (errors[name]) setErrors(prev => { const n = { ...prev }; delete n[name]; return n; });
        if (!touched) {
            setTouched(true);
            pushEvent('form_start', { landing_page: 'web-design-northern-ireland', locale: 'en' });
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        if (status === 'submitting') return;
        setStatus('submitting');
        setErrors({});

        try {
            const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';
            const res = await fetch('/en/web-design-northern-ireland/submit', {
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

            pushEvent('lead_created', {
                lead_id:      data.lead_id ?? undefined,
                lead_type:    'website',
                landing_page: 'web-design-northern-ireland',
                locale:       'en',
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
                    Thanks — your enquiry has been received.
                </h3>
                <p className="text-neutral-600 dark:text-white/70 mb-2">
                    I will review the information and respond within one business day.
                </p>
                <p className="text-sm text-neutral-500 dark:text-white/50">
                    For urgent questions, call or email directly.
                </p>
            </div>
        );
    }

    return (
        <form onSubmit={handleSubmit} noValidate className="space-y-5" aria-label="Website proposal request form">
            {/* Name */}
            <div>
                <label htmlFor="en-name" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Name <span className="text-brand-500">*</span>
                </label>
                <input
                    id="en-name" name="name" type="text" autoComplete="name"
                    value={form.name} onChange={handleChange} required
                    aria-invalid={!!errors.name}
                    aria-describedby={errors.name ? 'en-err-name' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.name ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="Jane Smith"
                />
                {errors.name && <p id="en-err-name" role="alert" className="mt-1 text-xs text-red-500">{errors.name}</p>}
            </div>

            {/* Email */}
            <div>
                <label htmlFor="en-email" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Email address <span className="text-brand-500">*</span>
                </label>
                <input
                    id="en-email" name="email" type="email" autoComplete="email"
                    value={form.email} onChange={handleChange} required
                    aria-invalid={!!errors.email}
                    aria-describedby={errors.email ? 'en-err-email' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.email ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="jane@yourbusiness.co.uk"
                />
                {errors.email && <p id="en-err-email" role="alert" className="mt-1 text-xs text-red-500">{errors.email}</p>}
            </div>

            {/* Phone */}
            <div>
                <label htmlFor="en-phone" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Phone number
                </label>
                <input
                    id="en-phone" name="phone" type="tel" autoComplete="tel"
                    value={form.phone} onChange={handleChange}
                    className="w-full rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                    placeholder="+44 7700 000000"
                />
            </div>

            {/* Current website */}
            <div>
                <label htmlFor="en-current-website" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Current website
                </label>
                <input
                    id="en-current-website" name="current_website" type="url"
                    value={form.current_website} onChange={handleChange}
                    aria-invalid={!!errors.current_website}
                    aria-describedby={errors.current_website ? 'en-err-current-website' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 ${errors.current_website ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="https://yourbusiness.co.uk (optional)"
                />
                {errors.current_website && <p id="en-err-current-website" role="alert" className="mt-1 text-xs text-red-500">{errors.current_website}</p>}
            </div>

            {/* Message */}
            <div>
                <label htmlFor="en-message" className="block text-sm font-medium text-neutral-700 dark:text-white/80 mb-1">
                    Tell me briefly what you need <span className="text-brand-500">*</span>
                </label>
                <textarea
                    id="en-message" name="message" rows={4}
                    value={form.message} onChange={handleChange} required
                    aria-invalid={!!errors.message}
                    aria-describedby={errors.message ? 'en-err-message' : undefined}
                    className={`w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-neutral-800 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500 resize-none ${errors.message ? 'border-red-500' : 'border-neutral-300 dark:border-white/20'}`}
                    placeholder="e.g. I run a plumbing company in Belfast and need a new website to get more local enquiries..."
                />
                {errors.message && <p id="en-err-message" role="alert" className="mt-1 text-xs text-red-500">{errors.message}</p>}
            </div>

            {/* GDPR */}
            <div className="flex items-start gap-3">
                <input
                    id="en-gdpr" name="gdpr_consent" type="checkbox"
                    checked={form.gdpr_consent} onChange={handleChange}
                    className="mt-1 h-4 w-4 flex-shrink-0 rounded border-neutral-300 text-brand-500 focus:ring-brand-500"
                />
                <label htmlFor="en-gdpr" className="text-xs text-neutral-500 dark:text-white/60 leading-relaxed">
                    I consent to my personal data being processed to prepare a proposal and make contact.
                    My data will not be shared with third parties and I can withdraw consent at any time.{' '}
                    <a href="/privacy-policy" target="_blank" className="underline hover:text-brand-500">Privacy Policy</a>
                </label>
            </div>
            {errors.gdpr_consent && <p id="en-err-gdpr" role="alert" className="text-xs text-red-500">{errors.gdpr_consent}</p>}

            {status === 'error' && (
                <p role="alert" className="text-sm text-red-500 text-center">
                    Something went wrong. Please try again or send an email directly.
                </p>
            )}

            <button
                type="submit"
                disabled={status === 'submitting'}
                onClick={() => pushEvent('primary_cta_click', { element: 'form_submit', landing_page: 'web-design-northern-ireland' })}
                className="w-full rounded-xl bg-brand-500 hover:bg-brand-600 disabled:opacity-60 text-white font-bold py-4 text-base transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
            >
                {status === 'submitting' ? 'Sending…' : 'Get a fixed-price proposal →'}
            </button>

            <p className="text-center text-xs text-neutral-400 dark:text-white/40">
                No obligation and no sales pressure. You will receive a personal response within one business day.
            </p>
        </form>
    );
}

// ─── Sticky Mobile CTA ────────────────────────────────────────────────────────

function StickyMobileCta({ onQuoteClick, contactPhone }: { onQuoteClick: () => void; contactPhone: string }) {
    const [hidden, setHidden] = useState(false);
    const lastScroll = useRef(0);

    useEffect(() => {
        const handler = () => {
            const current = window.scrollY;
            setHidden(current > lastScroll.current && current > 200);
            lastScroll.current = current;
        };
        window.addEventListener('scroll', handler, { passive: true });
        return () => window.removeEventListener('scroll', handler);
    }, []);

    return (
        <div
            className={`fixed bottom-0 inset-x-0 z-40 md:hidden bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-white/10 transition-transform duration-300 ${hidden ? 'translate-y-full' : 'translate-y-0'}`}
            style={{ paddingBottom: 'env(safe-area-inset-bottom)' }}
        >
            <div className="flex gap-2 px-4 py-3">
                <button
                    onClick={() => {
                        pushEvent('primary_cta_click', { element: 'sticky_cta', landing_page: 'web-design-northern-ireland' });
                        onQuoteClick();
                    }}
                    className="flex-1 rounded-xl bg-brand-500 text-white font-bold py-3 text-sm"
                >
                    Get a quote
                </button>
                <a
                    href={`tel:${contactPhone}`}
                    onClick={() => pushEvent('phone_click', { landing_page: 'web-design-northern-ireland' })}
                    className="flex-shrink-0 rounded-xl border border-neutral-300 dark:border-white/20 px-4 py-3 text-sm font-bold text-neutral-700 dark:text-white"
                >
                    Call
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
    founder_career_stat?: string;
    testimonials?: Array<{ quote: string; name: string; role: string; photo_url?: string; verify_url?: string }>;
    faq_items?: Array<{ q: string; a: string }>;
    form_title?: string;
    form_note?: string;
    contact_phone?: string;
    success_heading?: string;
    success_body?: string;
    og_image_url?: string;
}

interface Props {
    footer?: { extra?: Record<string, unknown> } | null;
    cms?: { title?: string; subtitle?: string; extra?: CmsExtra } | null;
}

export default function EnWebDesign({ footer, cms }: Props) {
    const formRef = useRef<HTMLElement | null>(null);

    const c = <T,>(key: keyof CmsExtra, fallback: T): T =>
        (cms?.extra?.[key] as T | undefined) ?? fallback;

    const heroTitle      = cms?.title || 'Professional Websites for Northern Ireland Businesses';
    const heroSubtitle   = cms?.subtitle || 'Get a fast, mobile-first website designed to build trust and generate more enquiries — with fixed pricing and direct contact with the developer.';
    const heroPriceLabel    = c('hero_price_label', 'Websites from £799');
    const heroDeliveryLabel = c('hero_delivery_label', 'Delivered in 3–5 weeks');
    const heroCtaSecondary  = c('hero_cta_secondary', 'View recent projects');
    const trustItems        = c('trust_items', [
        { icon: '📍', text: 'Based in Northern Ireland' },
        { icon: '⭐', text: '10+ years of commercial experience' },
        { icon: '📱', text: 'Mobile-first development' },
        { icon: '💷', text: 'Clear fixed pricing' },
    ]);
    const packageFrom       = c('package_from', '£799');
    const packageScope      = c('package_scope', [
        'Up to five core pages', 'Custom layout tailored to your business',
        'Mobile-first design', 'Contact form', 'Basic technical SEO',
        'Google Analytics and Search Console setup', 'SSL and form security',
        'CMS for editing your own content', 'Two rounds of revisions',
        'Deployment', 'Short handover training',
    ]);
    const packageExcluded   = c('package_excluded', [
        'Complex e-commerce functionality', 'Custom web applications',
        'Professional photography', 'Unlimited revisions',
        'Ongoing monthly SEO', 'Large numbers of integrations',
    ]);
    const noHiddenFees      = c('no_hidden_fees_note', 'No hidden fees. The final scope and price are confirmed in writing before work begins.');
    const contactPhone      = c('contact_phone', '+447882799050');
    const founderBio        = c('founder_bio', 'Website Expert is a founder-led web studio based in Northern Ireland. You speak directly with the person responsible for planning, designing, building and launching your project. No account managers. No outsourced communication. No unclear responsibility.');
    const founderName       = c('founder_name', 'Konrad');
    const founderPhoto      = c('founder_photo_url', '../storage/2342452345234.png');
    const founderLinkedin   = c('founder_linkedin_url', '');
    const founderLocation   = c('founder_location', 'Northern Ireland');
    const founderExp        = c('founder_experience', '10+ years experience');
    const founderCareerStat = c('founder_career_stat', '200+ projects contributed to');
    const testimonials      = c('testimonials', [
        { quote: 'The new website made our services much easier to understand and significantly increased the number of enquiries received through the contact form.', name: '', role: '', photo_url: '', verify_url: '' },
        { quote: 'Working directly with the developer made a huge difference. Every decision was made quickly and everything was delivered as promised.', name: '', role: '', photo_url: '', verify_url: '' },
        { quote: 'The fixed price and clear timeline meant there were no surprises. The whole process was straightforward from start to finish.', name: '', role: '', photo_url: '', verify_url: '' },
    ]);
    const faqItems          = c('faq_items', [
        { q: 'How much does a business website cost?', a: 'The basic package (up to 5 pages, contact form, technical SEO, CMS) starts from £799. The final price depends on the project scope and is confirmed in writing before work begins.' },
        { q: 'How long will the project take?', a: 'Typical delivery for the basic package is 3–5 weeks from scope confirmation and initial payment.' },
        { q: 'What do I need to provide?', a: 'Basic information about your business, a description of your services, contact details, and a few photos. I help gather and structure content during the project.' },
        { q: 'Will I own the website and domain?', a: 'Once the project has been fully paid, you receive ownership of the agreed website deliverables. The domain should always be registered in your name or business.' },
        { q: 'Can I update the website myself?', a: 'Yes. The website includes a CMS that allows you to edit content without any technical knowledge. Training is included in the basic package price.' },
        { q: 'What happens after launch?', a: 'I provide handover training and remain available for technical questions. Additional support, updates or expansion can be discussed separately.' },
    ]);
    const formTitle         = c('form_title', 'Get a fixed-price proposal');
    const formNote          = c('form_note', 'No obligation and no sales pressure. You will receive a personal response within one business day.');
    const ogImage           = c('og_image_url', '');

    const scrollToForm = () => {
        formRef.current?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        pushEvent('primary_cta_click', { element: 'hero_cta', landing_page: 'web-design-northern-ireland' });
    };

    const scrollToPortfolio = () => {
        document.getElementById('portfolio')?.scrollIntoView({ behavior: 'smooth' });
        pushEvent('portfolio_view', { landing_page: 'web-design-northern-ireland' });
    };

    const jsonLd = {
        '@context': 'https://schema.org',
        '@graph': [
            {
                '@type': 'Service',
                name: 'Professional Web Design for Northern Ireland Businesses',
                url: 'https://website-expert.uk/en/web-design-northern-ireland',
                areaServed: { '@type': 'AdministrativeArea', name: 'Northern Ireland' },
                provider: { '@type': 'ProfessionalService', name: 'Website Expert', url: 'https://website-expert.uk/' },
                offers: { '@type': 'Offer', priceCurrency: 'GBP', price: '799', description: 'Business websites from £799' },
            },
            {
                '@type': 'FAQPage',
                mainEntity: faqItems.map(item => ({
                    '@type': 'Question',
                    name: item.q,
                    acceptedAnswer: { '@type': 'Answer', text: item.a },
                })),
            },
            {
                '@type': 'BreadcrumbList',
                itemListElement: [
                    { '@type': 'ListItem', position: 1, name: 'Home', item: 'https://website-expert.uk/' },
                    { '@type': 'ListItem', position: 2, name: 'Web Design Northern Ireland', item: 'https://website-expert.uk/en/web-design-northern-ireland' },
                ],
            },
        ],
    };

    return (
        <MarketingLayout auth={null} footer={footer}>
            <Head>
                <title>Professional Web Design Northern Ireland</title>
                <meta name="description" content="Get a fast, mobile-first website designed to build trust and generate more enquiries. Fixed pricing, direct contact with the developer. Websites from £799." />
                <meta property="og:type" content="website" />
                <meta property="og:url" content="https://website-expert.uk/en/web-design-northern-ireland" />
                <meta property="og:title" content="Professional Web Design Northern Ireland — Website Expert" />
                <meta property="og:description" content="Websites from £799. Delivered in 3–5 weeks. No agency middlemen." />
                <meta property="og:locale" content="en_GB" />
                {ogImage && <meta property="og:image" content={ogImage} />}
                <meta name="twitter:card" content={ogImage ? 'summary_large_image' : 'summary'} />
                <meta name="twitter:title" content="Professional Web Design Northern Ireland — Website Expert" />
                <meta name="twitter:description" content="Websites from £799. Delivered in 3–5 weeks. No agency middlemen." />
                {ogImage && <meta name="twitter:image" content={ogImage} />}
                <script type="application/ld+json">{JSON.stringify(jsonLd)}</script>
            </Head>

            {/* ── SECTION 1: HERO ───────────────────────────────────────────── */}
            <section className="relative bg-gradient-to-br from-neutral-950 via-neutral-900 to-neutral-800 text-white pt-20 pb-16 md:pt-28 md:pb-24">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="max-w-3xl">
                        <div className="inline-flex items-center gap-2 rounded-full bg-white/10 border border-white/20 px-4 py-1.5 text-xs font-medium text-white/80 mb-6">
                            <span className="w-1.5 h-1.5 rounded-full bg-green-400 animate-pulse" />
                            Based in Northern Ireland · Direct contact with the developer
                        </div>

                        <h1 className="font-display text-4xl sm:text-5xl md:text-6xl font-black tracking-tight leading-tight mb-6">
                            {heroTitle.includes('Northern Ireland') ? (
                                <>Professional Websites for{' '}<span className="text-brand-400">Northern Ireland</span>{' '}Businesses</>
                            ) : heroTitle}
                        </h1>

                        <p className="text-lg text-white/80 leading-relaxed mb-8 max-w-2xl">
                            {heroSubtitle}
                        </p>

                        <div className="flex flex-wrap gap-3 mb-8 text-sm font-medium text-white/70">
                            <span>{heroPriceLabel}</span>
                            <span className="text-white/30">·</span>
                            <span>{heroDeliveryLabel}</span>
                            <span className="text-white/30">·</span>
                            <span>No agency middlemen</span>
                        </div>

                        <div className="flex flex-col sm:flex-row gap-3">
                            <button
                                onClick={scrollToForm}
                                className="inline-flex items-center justify-center rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-8 py-4 text-base transition-colors focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2 focus:ring-offset-neutral-900"
                            >
                                Get a fixed-price proposal
                            </button>
                            <button
                                onClick={scrollToPortfolio}
                                className="inline-flex items-center justify-center rounded-2xl border border-white/30 hover:border-white/60 text-white font-bold px-8 py-4 text-base transition-colors"
                            >
                                View recent projects
                            </button>
                        </div>

                        <div className="mt-8 flex flex-wrap gap-x-6 gap-y-2">
                            {trustItems.map((item, i) => (
                                <TrustItem key={i} icon={item.icon} text={item.text} />
                            ))}
                        </div>
                    </div>
                </div>
            </section>

            {/* ── SECTION 2: PROBLEM ────────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-12">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Your website should bring you business —{' '}
                            <span className="text-brand-500">not just exist online</span>
                        </h2>
                    </div>
                    <div className="grid md:grid-cols-3 gap-6 mb-10">
                        <ProblemCard
                            icon="😟"
                            title="Outdated design"
                            body="An old or poorly designed website can make a good business look unreliable to potential customers."
                        />
                        <ProblemCard
                            icon="📉"
                            title="Few enquiries"
                            body="Visitors leave when they cannot quickly understand your offer or find a clear way to contact you."
                        />
                        <ProblemCard
                            icon="🔧"
                            title="Difficult to manage"
                            body="You should not need a developer every time you want to update basic content on your own website."
                        />
                    </div>
                    <p className="text-center font-semibold text-neutral-700 dark:text-white/80">
                        We solve all three with a fast, clear and conversion-focused website.
                    </p>
                </div>
            </section>

            {/* ── SECTION 3: PACKAGE FROM £799 ─────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-10">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Website Launch — from <span className="text-brand-500">£799</span>
                        </h2>
                        <p className="mt-4 text-neutral-600 dark:text-white/70">
                            A defined scope and fixed price confirmed before work begins.
                        </p>
                    </div>

                    <div className="lg:grid lg:grid-cols-2 gap-8">
                        <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-800 p-6 mb-6 lg:mb-0">
                            <h3 className="font-bold text-neutral-900 dark:text-white mb-4">What's included</h3>
                            <ul className="space-y-2.5">
                                {[
                                    'Up to five core pages',
                                    'Custom layout tailored to your business',
                                    'Mobile-first design',
                                    'Contact form',
                                    'Basic technical SEO',
                                    'Google Analytics and Search Console setup',
                                    'SSL and form security',
                                    'CMS for editing your own content',
                                    'Two rounds of revisions',
                                    'Deployment',
                                    'Short handover training',
                                ].map(item => (
                                    <li key={item} className="flex items-start gap-2 text-sm text-neutral-700 dark:text-white/80">
                                        <span className="text-green-500 font-bold flex-shrink-0 mt-0.5">✓</span>
                                        {item}
                                    </li>
                                ))}
                            </ul>
                        </div>

                        <div className="space-y-6">
                            <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-800 p-6">
                                <h3 className="font-bold text-neutral-900 dark:text-white mb-4">Not included in the basic package</h3>
                                <ul className="space-y-2">
                                    {[
                                        'Complex e-commerce functionality',
                                        'Custom web applications',
                                        'Professional photography',
                                        'Unlimited revisions',
                                        'Ongoing monthly SEO',
                                        'Large numbers of integrations',
                                    ].map(item => (
                                        <li key={item} className="flex items-start gap-2 text-sm text-neutral-500 dark:text-white/50">
                                            <span className="flex-shrink-0 mt-0.5">—</span>
                                            {item}
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="rounded-2xl bg-brand-500/10 border border-brand-500/30 p-5">
                                <p className="font-bold text-neutral-900 dark:text-white mb-1">No hidden fees</p>
                                <p className="text-sm text-neutral-600 dark:text-white/70">{noHiddenFees}</p>
                            </div>
                        </div>
                    </div>

                    <div className="mt-8 text-center">
                        <button onClick={scrollToForm} className="rounded-2xl bg-brand-500 hover:bg-brand-600 text-white font-bold px-8 py-4 text-base transition-colors">
                            Get a fixed-price proposal →
                        </button>
                    </div>
                </div>
            </section>

            {/* ── SECTION 4: PORTFOLIO ──────────────────────────────────────── */}
            <section id="portfolio" className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        Recent projects
                    </h2>
                    <div className="grid md:grid-cols-3 gap-6">
                        {[
                            {
                                industry: 'Dental Practice · Northern Ireland',
                                title: 'Dental clinic website',
                                challenge: 'The old website made online booking difficult and did not clearly present services to new patients.',
                                solution: 'Mobile-first redesign, online booking integration and Google Reviews section.',
                                result: 'Significant increase in new-patient enquiries through the contact form.',
                            },
                            {
                                industry: 'Trades Business · Belfast',
                                title: 'Local trades company',
                                challenge: 'No website — potential customers could not verify the business or find contact details online.',
                                solution: 'New website with project gallery, quote request form and customer reviews.',
                                result: 'Steady flow of new enquiries from local search results.',
                            },
                            {
                                industry: 'Professional Services · NI',
                                title: 'Legal services firm',
                                challenge: 'Existing website did not build confidence or explain services in a way clients could understand.',
                                solution: 'Clear service pages, team bios and a defined enquiry process.',
                                result: 'Improved quality of inbound enquiries and reduced time spent on unqualified calls.',
                            },
                        ].map(item => (
                            <div key={item.title} className="rounded-2xl bg-neutral-50 dark:bg-neutral-900 border border-neutral-200 dark:border-white/10 p-6">
                                <p className="text-xs font-medium text-brand-500 uppercase tracking-widest mb-2">{item.industry}</p>
                                <h3 className="font-bold text-neutral-900 dark:text-white mb-4">{item.title}</h3>
                                <div className="space-y-3 text-sm">
                                    <div>
                                        <p className="font-semibold text-neutral-500 dark:text-white/50 text-xs uppercase tracking-wide mb-1">Challenge</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.challenge}</p>
                                    </div>
                                    <div>
                                        <p className="font-semibold text-neutral-500 dark:text-white/50 text-xs uppercase tracking-wide mb-1">Solution</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.solution}</p>
                                    </div>
                                    <div className="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700/40 p-3">
                                        <p className="font-semibold text-green-700 dark:text-green-400 text-xs uppercase tracking-wide mb-1">Result</p>
                                        <p className="text-neutral-700 dark:text-white/80">{item.result}</p>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SECTION 5: ABOUT FOUNDER ──────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <div className="lg:grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            {founderPhoto ? (
                                <img
                                    src={founderPhoto}
                                    alt={founderName ? `${founderName}, web developer at Website Expert` : 'Website Expert founder'}
                                    width={192}
                                    height={192}
                                    loading="lazy"
                                    decoding="async"
                                    className="w-48 h-48 rounded-2xl object-cover mb-6"
                                />
                            ) : (
                                <div className="w-48 h-48 rounded-2xl bg-neutral-200 dark:bg-neutral-700 mb-6 flex items-center justify-center text-neutral-400 dark:text-neutral-500 text-sm text-center px-4">
                                    Founder photo<br/>placeholder
                                </div>
                            )}
                            <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white mb-4">
                                Work directly with the developer building your website
                            </h2>
                            <p className="text-neutral-600 dark:text-white/70 leading-relaxed mb-4">
                                {founderBio}
                            </p>
                            <p className="text-neutral-600 dark:text-white/70 leading-relaxed mb-6">
                                No account managers. No outsourced communication. No unclear responsibility.
                            </p>
                            <div className="flex flex-wrap gap-3">
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">{founderExp}</span>
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">{founderLocation}</span>
                                <span className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-neutral-600 dark:text-white/70">{founderCareerStat}</span>
                                {founderLinkedin && (
                                    <a href={founderLinkedin} target="_blank" rel="noopener noreferrer" className="rounded-full bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 px-3 py-1.5 text-xs font-medium text-brand-500 hover:underline">LinkedIn</a>
                                )}
                            </div>
                        </div>
                        <div className="mt-8 lg:mt-0">
                            <h3 className="font-bold text-neutral-900 dark:text-white mb-4">Clear terms from the start</h3>
                            <div className="space-y-3">
                                {[
                                    ['📄', 'Written project scope agreed before work begins'],
                                    ['💷', 'Fixed price — no unexpected charges'],
                                    ['📅', 'Realistic delivery schedule'],
                                    ['🔄', 'Defined number of revision rounds included'],
                                    ['🌐', 'Domain registered in your name or business'],
                                    ['🔑', 'Full access to the website after project completion'],
                                    ['🧾', 'Invoice and full project documentation'],
                                    ['🤝', 'No long-term maintenance contract unless you need one'],
                                ].map(([icon, text]) => (
                                    <div key={text as string} className="flex items-start gap-3">
                                        <span className="text-xl flex-shrink-0">{icon}</span>
                                        <p className="text-sm text-neutral-700 dark:text-white/80 leading-relaxed">{text}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {/* ── SECTION 6: PROCESS ───────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        How we work together
                    </h2>
                    <div className="grid sm:grid-cols-2 gap-8 mb-8">
                        <ProcessStep num={1} title="Discovery" body="We discuss your business, customers, goals and required functionality." />
                        <ProcessStep num={2} title="Proposal" body="You receive a defined scope, fixed price and realistic delivery schedule." />
                        <ProcessStep num={3} title="Design and development" body="Your website is designed, built and tested with regular progress updates." />
                        <ProcessStep num={4} title="Launch and support" body="We launch the website, provide training and remain available after delivery." />
                    </div>
                    <p className="text-center text-sm text-neutral-500 dark:text-white/50">
                        Typical delivery time: <strong className="text-neutral-700 dark:text-white/80">3–5 weeks</strong>
                    </p>
                </div>
            </section>

            {/* ── SECTION 7: TESTIMONIALS ──────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        What clients say
                    </h2>
                    <div className="grid md:grid-cols-3 gap-6">
                        {testimonials.map((t, i) => (
                            <div key={i} className="rounded-2xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 p-6 flex flex-col">
                                <div className="flex gap-0.5 mb-4">
                                    {[1,2,3,4,5].map(s => <span key={s} className="text-yellow-400 text-sm">★</span>)}
                                </div>
                                <p className="text-neutral-700 dark:text-white/80 text-sm leading-relaxed flex-1 mb-4 italic">
                                    "{t.quote}"
                                </p>
                                <div className="flex items-center gap-3">
                                    {t.photo_url ? (
                                        <img src={t.photo_url} alt={t.name} className="w-8 h-8 rounded-full object-cover flex-shrink-0" />
                                    ) : (
                                        <div className="w-8 h-8 rounded-full bg-neutral-200 dark:bg-neutral-600 flex-shrink-0" />
                                    )}
                                    <div>
                                        {t.name ? (
                                            <p className="text-sm font-semibold text-neutral-900 dark:text-white">{t.name}</p>
                                        ) : (
                                            <p className="text-sm font-semibold text-neutral-400 dark:text-white/40 italic">Name — add before launch</p>
                                        )}
                                        {t.role && <p className="text-xs text-neutral-500 dark:text-white/50">{t.role}</p>}
                                    </div>
                                </div>
                                {t.verify_url && (
                                    <a href={t.verify_url} target="_blank" rel="noopener noreferrer" className="mt-2 text-xs text-brand-500 hover:underline">Verify ↗</a>
                                )}
                            </div>
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SECTION 8: FAQ ───────────────────────────────────────────── */}
            <section className="py-16 md:py-24 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white text-center mb-10">
                        Frequently asked questions
                    </h2>
                    <div>
                        {faqItems.map((item, i) => (
                            <FaqItem key={i} q={item.q} a={item.a} />
                        ))}
                    </div>
                </div>
            </section>

            {/* ── SECTION 9: FORM ──────────────────────────────────────────── */}
            <section
                id="contact-form"
                ref={formRef as React.RefObject<HTMLElement>}
                className="py-16 md:py-24 bg-neutral-50 dark:bg-neutral-900"
            >
                <div className="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8">
                    <div className="text-center mb-8">
                        <h2 className="font-display text-3xl sm:text-4xl font-black tracking-tight text-neutral-900 dark:text-white">
                            Get a{' '}
                            <span className="text-brand-500">{formTitle.replace('Get a ', '').replace('fixed-price proposal', '') ? formTitle : 'fixed-price proposal'}</span>
                        </h2>
                        <p className="mt-4 text-neutral-600 dark:text-white/70">
                            {formNote}
                        </p>
                    </div>
                    <div className="rounded-2xl bg-white dark:bg-neutral-800 border border-neutral-200 dark:border-white/10 p-6 sm:p-8">
                        <ContactForm formRef={formRef} />
                    </div>
                    <div className="mt-6 text-center">
                        <p className="text-sm text-neutral-500 dark:text-white/50">
                            Polish-speaking business in the UK?{' '}
                            <a href="/pl/strony-internetowe-uk" className="text-brand-500 hover:underline font-medium">
                                We also offer full project support in Polish →
                            </a>
                        </p>
                    </div>
                </div>
            </section>

            {/* Sticky mobile CTA */}
            <StickyMobileCta onQuoteClick={scrollToForm} contactPhone={contactPhone} />

            {/* Mobile spacer */}
            <div className="h-16 md:hidden" />
        </MarketingLayout>
    );
}
