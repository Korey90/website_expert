import React, { useState, useRef, useEffect } from 'react';
import { Head, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';
import useScrollReveal from '@/Hooks/useScrollReveal';

const ICON_PATHS = {
    'monitor':       <path strokeLinecap="round" strokeLinejoin="round" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9" />,
    'shopping-cart': <path strokeLinecap="round" strokeLinejoin="round" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />,
    'code':          <path strokeLinecap="round" strokeLinejoin="round" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />,
    'search':        <path strokeLinecap="round" strokeLinejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />,
    'bar-chart':     <path strokeLinecap="round" strokeLinejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />,
    'settings':      <path strokeLinecap="round" strokeLinejoin="round" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />,
    'shield':        <path strokeLinecap="round" strokeLinejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />,
    'pencil':        <path strokeLinecap="round" strokeLinejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />,
    'zap':           <path strokeLinecap="round" strokeLinejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />,
    'file-text':     <path strokeLinecap="round" strokeLinejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />,
};

const labels = {
    en: {
        back:           '← Back to Services',
        priceFrom:      'Starting from',
        whatsIncluded:  "What's Included",
        faqTitle:       'Frequently Asked Questions',
        getQuote:       'Get a Free Quote',
        ctaDesc:        "Ready to get started? Get in touch and we'll send you a tailored proposal.",
        ctaFormTitle:   'Tell us about your needs',
        labelName:      'Full name',
        labelContact:   'Email or phone number',
        labelMessage:   'Message',
        placeholderName:    'John Smith',
        placeholderContact: 'email or phone',
        gdprText:       'I agree to the processing of my personal data in accordance with the',
        gdprLink:       'privacy policy',
        submitBtn:      'Send enquiry',
        sending:        'Sending…',
        successTitle:   "We'll be in touch!",
        successMsg:     "Thanks for reaching out. We'll get back to you within 24 business hours.",
        errorMsg:       'Something went wrong. Please check your details and try again.',
        validationMsg:  'Please fill in your name, contact details and message.',
        clearMessage:   'Clear message',
    },
    pl: {
        back:           '← Powrót do usług',
        priceFrom:      'Cena od',
        whatsIncluded:  'Co otrzymujesz',
        faqTitle:       'Najczęściej zadawane pytania',
        getQuote:       'Zapytaj o wycenę',
        ctaDesc:        'Gotowy, żeby zacząć? Skontaktuj się z nami, a wyślemy Ci spersonalizowaną ofertę.',
        ctaFormTitle:   'Powiedz nam o swoich potrzebach',
        labelName:      'Imię i nazwisko',
        labelContact:   'Email lub numer telefonu',
        labelMessage:   'Wiadomość',
        placeholderName:    'Jan Kowalski',
        placeholderContact: 'email lub telefon',
        gdprText:       'Wyrażam zgodę na przetwarzanie moich danych osobowych zgodnie z',
        gdprLink:       'polityką prywatności',
        submitBtn:      'Wyślij zapytanie',
        sending:        'Wysyłanie…',
        successTitle:   'Odezwiemy się wkrótce!',
        successMsg:     'Dziękujemy za kontakt. Odpiszemy w ciągu 24 godzin roboczych.',
        errorMsg:       'Wystąpił błąd. Sprawdź dane i spróbuj ponownie.',
        validationMsg:  'Uzupełnij imię, dane kontaktowe i wiadomość.',
        clearMessage:   'Wyczyść wiadomość',
    },
    pt: {
        back:           '← Voltar aos Serviços',
        priceFrom:      'A partir de',
        whatsIncluded:  'O Que Está Incluído',
        faqTitle:       'Perguntas Frequentes',
        getQuote:       'Pedir Orçamento',
        ctaDesc:        'Pronto para começar? Entre em contacto e enviaremos uma proposta personalizada.',
        ctaFormTitle:   'Fale-nos sobre as suas necessidades',
        labelName:      'Nome completo',
        labelContact:   'Email ou número de telefone',
        labelMessage:   'Mensagem',
        placeholderName:    'João Silva',
        placeholderContact: 'email ou telefone',
        gdprText:       'Concordo com o processamento dos meus dados pessoais de acordo com a',
        gdprLink:       'política de privacidade',
        submitBtn:      'Enviar pedido',
        sending:        'A enviar…',
        successTitle:   'Entraremos em contacto!',
        successMsg:     'Obrigado pelo contacto. Responderemos em 24 horas úteis.',
        errorMsg:       'Ocorreu um erro. Verifique os seus dados e tente novamente.',
        validationMsg:  'Preencha o nome, contacto e mensagem.',
        clearMessage:   'Limpar mensagem',
    },
};

function FaqAccordion({ items, locale }) {
    const faqKey = (i, side) => `${side}_${locale}` in i ? `${side}_${locale}` : `${side}_en`;

    return (
        <div className="divide-y divide-neutral-200 dark:divide-neutral-800">
            {items.map((item, idx) => (
                <FaqItem key={idx} question={item[faqKey(item, 'q')]} answer={item[faqKey(item, 'a')]} />
            ))}
        </div>
    );
}

function FaqItem({ question, answer }) {
    const [open, setOpen] = useState(false);

    if (!question) return null;

    return (
        <div className="py-4">
            <button
                onClick={() => setOpen(o => !o)}
                className="flex w-full items-center justify-between gap-4 text-left text-neutral-900 dark:text-white font-medium text-sm sm:text-base group"
            >
                <span>{question}</span>
                <span className={`shrink-0 w-5 h-5 text-brand-500 transition-transform ${open ? 'rotate-45' : ''}`}>
                    <svg fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 4v16m8-8H4" />
                    </svg>
                </span>
            </button>
            {open && (
                <p className="mt-3 text-sm text-neutral-600 dark:text-neutral-400 leading-relaxed">
                    {answer}
                </p>
            )}
        </div>
    );
}

const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition text-sm';
const labelClass = 'block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5';

function detectContactType(value) {
    if (!value) return 'unknown';
    if (/@/.test(value)) return 'email';
    if (/^[\+\d\s\(\)\-]{3,}$/.test(value.trim())) return 'phone';
    return 'unknown';
}

function ContactTypeIcon({ type }) {
    if (type === 'email') return (
        <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
        </svg>
    );
    if (type === 'phone') return (
        <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path strokeLinecap="round" strokeLinejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
        </svg>
    );
    return null;
}

function CtaTeaser({ l, ctaLabel, onOpen }) {
    return (
        <div className="rounded-2xl bg-brand-50 dark:bg-brand-950/20 border border-brand-100 dark:border-brand-900 px-7 py-8">
            <p className="text-sm text-neutral-600 dark:text-neutral-400 mb-5">{l.ctaDesc}</p>
            <button
                onClick={onOpen}
                className="inline-flex items-center gap-2 px-5 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-md shadow-brand-500/20"
            >
                {ctaLabel}
                <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                </svg>
            </button>
        </div>
    );
}

function SuccessCard({ l }) {
    return (
        <div className="rounded-2xl bg-green-50 dark:bg-green-950/20 border border-green-200 dark:border-green-900 px-7 py-8 text-center">
            <div className="mx-auto mb-4 w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/40 flex items-center justify-center">
                <svg className="w-6 h-6 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5">
                    <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                </svg>
            </div>
            <p className="text-base font-semibold text-neutral-900 dark:text-white mb-1">{l.successTitle}</p>
            <p className="text-sm text-neutral-600 dark:text-neutral-400">{l.successMsg}</p>
        </div>
    );
}

function InlineContactForm({ l, locale, serviceTitle, serviceSlug }) {
    const msgRef = useRef(null);
    const [name, setName]               = useState('');
    const [contact, setContact]         = useState('');
    const [contactType, setContactType] = useState('unknown');
    const [message, setMessage]         = useState('');
    const [gdpr, setGdpr]               = useState(false);
    const [status, setStatus]           = useState('idle'); // idle | sending | error | validation
    const [submitted, setSubmitted]     = useState(false);

    useEffect(() => {
        const prefill = {
            en: `Hi, I'm interested in your "${serviceTitle}" service. Could you send me more details and a quote?`,
            pl: `Dzień dobry, jestem zainteresowany/a usługą "${serviceTitle}". Czy możecie przesłać więcej szczegółów i wycenę?`,
            pt: `Olá, tenho interesse no serviço "${serviceTitle}". Poderiam enviar mais detalhes e um orçamento?`,
        };
        setMessage(prefill[locale] ?? prefill.en);
    }, []);

    const handleContactChange = (v) => {
        setContact(v);
        setContactType(detectContactType(v));
    };

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!name.trim() || !contact.trim() || !message.trim() || !gdpr) {
            setStatus('validation');
            return;
        }
        setStatus('sending');

        const payload = {
            name,
            message,
            gdpr_consent: gdpr,
            service_slug: serviceSlug ?? null,
        };
        if (contactType === 'email') {
            payload.email = contact;
        } else {
            payload.phone = contact;
        }

        try {
            const res = await fetch('/contact/quick', {
                method:  'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'Accept':        'application/json',
                },
                body: JSON.stringify(payload),
            });
            if (!res.ok) {
                setStatus('error');
                return;
            }
            setSubmitted(true);
        } catch {
            setStatus('error');
        }
    };

    if (submitted) return <SuccessCard l={l} />;

    return (
        <div className="rounded-2xl bg-white dark:bg-neutral-950 border border-neutral-100 dark:border-neutral-800 px-7 py-7">
            <p className="text-sm font-semibold text-neutral-900 dark:text-white mb-5">{l.ctaFormTitle}</p>
            <form onSubmit={handleSubmit} noValidate>
                {/* Name */}
                <div className="mb-4">
                    <label htmlFor="qcf-name" className={labelClass}>
                        {l.labelName} <span className="text-brand-500" aria-hidden="true">*</span>
                    </label>
                    <input
                        id="qcf-name" type="text" required autoComplete="name"
                        value={name} onChange={e => setName(e.target.value)}
                        className={inputClass} placeholder={l.placeholderName}
                    />
                </div>

                {/* Contact (email or phone) */}
                <div className="mb-4">
                    <label htmlFor="qcf-contact" className={labelClass}>
                        {l.labelContact} <span className="text-brand-500" aria-hidden="true">*</span>
                    </label>
                    <div className="relative">
                        <input
                            id="qcf-contact" type="text" required autoComplete="email"
                            value={contact} onChange={e => handleContactChange(e.target.value)}
                            className={`${inputClass} ${contactType !== 'unknown' ? 'pr-10' : ''}`}
                            placeholder={l.placeholderContact}
                        />
                        {contactType !== 'unknown' && (
                            <span className="absolute right-3 top-1/2 -translate-y-1/2 pointer-events-none">
                                <ContactTypeIcon type={contactType} />
                            </span>
                        )}
                    </div>
                </div>

                {/* Message with clear button */}
                <div className="mb-4">
                    <label htmlFor="qcf-message" className={labelClass}>
                        {l.labelMessage} <span className="text-brand-500" aria-hidden="true">*</span>
                    </label>
                    <div className="relative">
                        <textarea
                            id="qcf-message" ref={msgRef} required rows={4}
                            value={message} onChange={e => setMessage(e.target.value)}
                            className={`${inputClass} resize-none pr-8`}
                        />
                        {message && (
                            <button
                                type="button"
                                title={l.clearMessage}
                                aria-label={l.clearMessage}
                                onClick={() => { setMessage(''); msgRef.current?.focus(); }}
                                className="absolute top-2 right-2 w-5 h-5 flex items-center justify-center rounded-full bg-neutral-200 dark:bg-neutral-700 hover:bg-neutral-300 dark:hover:bg-neutral-600 text-neutral-500 dark:text-neutral-400 transition"
                            >
                                <svg className="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="3">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        )}
                    </div>
                </div>

                {/* GDPR */}
                <div className="mb-5">
                    <label className="flex items-start gap-3 cursor-pointer">
                        <input
                            type="checkbox" checked={gdpr} onChange={e => setGdpr(e.target.checked)}
                            className="mt-0.5 w-4 h-4 rounded border-neutral-300 text-brand-500 focus:ring-brand-500 shrink-0"
                        />
                        <span className="text-xs text-neutral-500 dark:text-neutral-400">
                            {l.gdprText}{' '}
                            <a href="/privacy-policy" className="text-brand-500 hover:underline">{l.gdprLink}</a>.
                        </span>
                    </label>
                </div>

                {/* Submit */}
                <button
                    type="submit" disabled={status === 'sending'}
                    className="w-full flex items-center justify-center gap-2 px-6 py-3 rounded-xl bg-brand-500 text-white text-sm font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-md shadow-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed"
                >
                    {status === 'sending' ? l.sending : l.submitBtn}
                    <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                </button>

                {status === 'validation' && (
                    <p className="mt-3 text-xs text-center text-red-600 dark:text-red-400">{l.validationMsg}</p>
                )}
                {status === 'error' && (
                    <p className="mt-3 text-xs text-center text-red-600 dark:text-red-400">{l.errorMsg}</p>
                )}
            </form>
        </div>
    );
}

export default function ServicesShow({ locale: localeProp, item, auth }) {
    useScrollReveal('.reveal');

    const { navbar, footer } = usePage().props;
    const locale = localeProp ?? 'en';
    const l = labels[locale] ?? labels.en;

    const [showForm, setShowForm] = useState(false);

    const t = (key) => item?.[`${key}_${locale}`] ?? item?.[`${key}_en`] ?? '';

    const title       = t('title');
    const desc        = t('description');
    const body        = t('body');
    const badge       = t('badge_text');
    const ctaLabel    = t('cta_label') || l.getQuote;
    const metaTitle   = t('meta_title') || `${title} – Website Expert`;
    const metaDesc    = t('meta_description') || desc;
    const iconPath    = ICON_PATHS[item?.icon] ?? ICON_PATHS['settings'];
    const features    = Array.isArray(item?.features) ? item.features : [];
    const faqItems    = Array.isArray(item?.faq) ? item.faq : [];

    const featureText = (f) => f?.[`text_${locale}`] ?? f?.text_en ?? '';

    return (
        <MarketingLayout auth={auth} navbar={navbar} footer={footer}>
            <Head>
                <title>{metaTitle}</title>
                <meta name="description" content={metaDesc} />
            </Head>

            {/* ── Hero ────────────────────────────────────────────── */}
            <section className="py-20 md:py-28 bg-white dark:bg-neutral-950">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                    <a
                        href="/services"
                        className="inline-flex items-center text-sm text-neutral-500 dark:text-neutral-400 hover:text-brand-500 mb-10 transition-colors"
                    >
                        {l.back}
                    </a>

                    <div className="flex flex-col sm:flex-row items-start gap-6 mb-6 reveal">
                        {/* Icon */}
                        <div className="w-16 h-16 rounded-2xl bg-brand-500/10 flex items-center justify-center shrink-0">
                            <svg className="w-8 h-8 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                {iconPath}
                            </svg>
                        </div>

                        <div className="flex-1 min-w-0">
                            {badge && (
                                <span className="inline-block mb-2 px-3 py-0.5 rounded-full text-xs font-semibold bg-brand-500/10 text-brand-600 dark:text-brand-400 uppercase tracking-wider">
                                    {badge}
                                </span>
                            )}
                            <h1 className="font-display text-3xl sm:text-4xl font-bold text-neutral-900 dark:text-white leading-tight mb-2">
                                {title}
                            </h1>
                            {item?.price_from && (
                                <p className="text-sm font-semibold text-brand-500">
                                    {l.priceFrom} <span className="text-base">{item.price_from}</span>
                                </p>
                            )}
                        </div>
                    </div>

                    {/* Service image */}
                    {item?.image_url && (
                        <div className="mb-10 rounded-2xl overflow-hidden reveal">
                            <img
                                src={item.image_url}
                                alt={title}
                                className="w-full h-auto object-cover max-h-80"
                                loading="lazy"
                            />
                        </div>
                    )}

                    {/* Short description */}
                    {desc && (
                        <p className="text-base text-neutral-600 dark:text-neutral-400 leading-relaxed mb-8 reveal">
                            {desc}
                        </p>
                    )}
                </div>
            </section>

            {/* ── Body (rich text) ───────────────────────────────── */}
            {body && (
                <section className="pb-16 bg-white dark:bg-neutral-950">
                    <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <div
                            className="prose prose-neutral dark:prose-invert max-w-none text-sm sm:text-base leading-relaxed reveal"
                            dangerouslySetInnerHTML={{ __html: body }}
                        />
                    </div>
                </section>
            )}

            {/* ── Features / What's included ────────────────────── */}
            {features.length > 0 && (
                <section className="py-14 bg-neutral-50 dark:bg-neutral-900">
                    <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                        <h2 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-8 reveal">
                            {l.whatsIncluded}
                        </h2>
                        <ul className="grid sm:grid-cols-2 gap-3 reveal">
                            {features.map((f, idx) => {
                                const text = featureText(f);
                                if (!text) return null;
                                return (
                                    <li key={idx} className="flex items-start gap-3">
                                        <span className="mt-0.5 shrink-0 w-5 h-5 rounded-full bg-brand-500/10 flex items-center justify-center">
                                            <svg className="w-3 h-3 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="3">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </span>
                                        <span className="text-sm text-neutral-700 dark:text-neutral-300">{text}</span>
                                    </li>
                                );
                            })}
                        </ul>
                    </div>
                </section>
            )}

            {/* ── FAQ ──────────────────────────────────────────────── */}
            {faqItems.length > 0 && (
                <section className="py-14 bg-white dark:bg-neutral-950">
                    <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                        <h2 className="font-display text-2xl font-bold text-neutral-900 dark:text-white mb-8 reveal">
                            {l.faqTitle}
                        </h2>
                        <div className="reveal">
                            <FaqAccordion items={faqItems} locale={locale} />
                        </div>
                    </div>
                </section>
            )}

            {/* ── CTA ──────────────────────────────────────────────── */}
            <section className="py-16 bg-neutral-50 dark:bg-neutral-900">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    {!showForm ? (
                        <CtaTeaser
                            l={l}
                            ctaLabel={ctaLabel}
                            onOpen={() => setShowForm(true)}
                        />
                    ) : (
                        <InlineContactForm
                            l={l}
                            locale={locale}
                            serviceTitle={title}
                            serviceSlug={item?.slug}
                        />
                    )}
                </div>
            </section>
        </MarketingLayout>
    );
}
