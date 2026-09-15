import { useState, useRef, useEffect } from 'react';
import { Link } from '@inertiajs/react';

const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition text-sm';
const labelClass = 'block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5';

function detectContactType(value) {
    if (!value) return 'unknown';
    if (/@/.test(value)) return 'email';
    if (/^[+\d\s()-]{3,}$/.test(value.trim())) return 'phone';
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

/**
 * Reusable quick-contact form (name + email/phone + message + GDPR) posting to /contact/quick.
 * `l` must provide: ctaFormTitle, labelName, labelContact, labelMessage, placeholderName,
 * placeholderContact, gdprText, gdprLink, submitBtn, sending, successTitle, successMsg,
 * errorMsg, validationMsg, clearMessage. `labelWebsite`/`placeholderWebsite` are only needed
 * when `initialWebsite` is passed (e.g. from the Google Rank Checker).
 */
export default function QuickContactForm({ l, locale, serviceTitle, serviceSlug, initialWebsite }) {
    const msgRef = useRef(null);
    const [name, setName]               = useState('');
    const [contact, setContact]         = useState('');
    const [contactType, setContactType] = useState('unknown');
    const [message, setMessage]         = useState('');
    const [website, setWebsite]         = useState(initialWebsite ?? '');
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
            current_website: website.trim() || null,
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

                {/* Website (only shown when we already have context, e.g. from the rank checker) */}
                {typeof initialWebsite === 'string' && initialWebsite !== '' && (
                    <div className="mb-4">
                        <label htmlFor="qcf-website" className={labelClass}>
                            {l.labelWebsite}
                        </label>
                        <input
                            id="qcf-website" type="text" autoComplete="url"
                            value={website} onChange={e => setWebsite(e.target.value)}
                            className={inputClass} placeholder={l.placeholderWebsite}
                        />
                    </div>
                )}

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
                            <Link href="/privacy-policy" className="text-brand-500 hover:underline">{l.gdprLink}</Link>.
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
