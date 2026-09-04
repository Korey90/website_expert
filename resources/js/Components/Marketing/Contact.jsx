import { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import { pushEvent } from '@/utils/dataLayer';

const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition';
const labelClass = 'block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5';

const PROJECT_TYPE_DEFAULTS = [
    { value: 'wizytowka', label_en: 'Brochure website',     label_pl: 'Strona wizytówkowa' },
    { value: 'ecommerce', label_en: 'E-commerce store',     label_pl: 'Sklep e-commerce' },
    { value: 'aplikacja', label_en: 'Web application',      label_pl: 'Aplikacja webowa' },
    { value: 'seo',       label_en: 'SEO / Positioning',    label_pl: 'SEO / Pozycjonowanie' },
    { value: 'reklama',   label_en: 'Advertising campaign', label_pl: 'Kampania reklamowa' },
    { value: 'inne',      label_en: 'Other',                label_pl: 'Inne' },
];

const DEFAULTS = {
    title:         { en: "Let's talk about your project",                                                   pl: 'Porozmawiajmy o Twoim projekcie' },
    subtitle:      { en: "Get in touch and we'll reply within 24 business hours. First 30 min is free.",    pl: 'Napisz do nas, a odezwiemy się w ciągu 24 godzin roboczych. Pierwsze 30 minut konsultacji jest bezpłatne.' },
    section_label: { en: 'Contact',                                                                          pl: 'Kontakt' },
};

export default function Contact({ data = null }) {
    const { locale = 'en' } = usePage().props;
    const [form, setForm]     = useState({ name: '', contact: '', project_type: '', message: '', gdpr_consent: false });
    const [status, setStatus] = useState('idle');

    const set = (k, v) => setForm(prev => ({ ...prev, [k]: v }));

    const extra = data?.extra ?? {};
  
    const t     = (key, fallback = '') => extra[`${key}_${locale}`] ?? extra[`${key}_en`] ?? fallback;

    const title        = (locale === 'pl' ? data?.title?.pl : data?.title?.en) || data?.title || DEFAULTS.title[locale];
    const subtitle     = (locale === 'pl' ? data?.subtitle?.pl : data?.subtitle?.en) || data?.subtitle || DEFAULTS.subtitle[locale];
    const sectionLabel = t('section_label') || DEFAULTS.section_label[locale];

    const email       = extra.email      || 'sales@websiteexpert.co.uk';
    const phone       = extra.phone      || '+44 000 000 200';
    const phoneHref   = extra.phone_href || 'tel:+44000000000';
    const privacyUrl  = extra.privacy_url || '#';

    const projectTypes  = extra.project_types  || PROJECT_TYPE_DEFAULTS;

    const chooseLabel        = locale === 'pl' ? 'Wybierz' : locale === 'pt' ? 'Escolher' : 'Choose';
    const contactLabel       = locale === 'pl' ? 'Email lub telefon' : locale === 'pt' ? 'Email ou telefone' : 'Email or phone';
    const contactPlaceholder = locale === 'pl' ? 'jan@firma.pl lub +48 000 000 000' : locale === 'pt' ? 'joao@empresa.pt ou +351 000 000 000' : 'info@yourcompany.co.uk or +44 7700 000000';

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.name || !form.contact || !form.message || !form.gdpr_consent) {
            setStatus('error');
            return;
        }
        setStatus('sending');
        try {
            const res = await fetch(route('contact.store'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body: JSON.stringify(form),
            });
            if (!res.ok) {
                setStatus('error');
                return;
            }
            pushEvent('generate_lead', {
                lead_source:  'contact',
                project_type: form.project_type,
            });
            if (typeof window.fbq === 'function') window.fbq('track', 'Lead');
            setStatus('success');
        } catch {
            setStatus('error');
        }
    };

    return (
        <section id="contact" className="py-20 md:py-28 bg-neutral-50 dark:bg-neutral-900">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 lg:gap-20">

                {/* Info panel */}
                <div className="reveal">
                    <span className="section-label">{sectionLabel}</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 mb-6 text-neutral-900 dark:text-white">
                        {title}
                    </h2>
                    <p className="text-neutral-600 dark:text-neutral-400 leading-relaxed mb-8">
                        {subtitle}
                    </p>
                    <ul className="space-y-4">
                        <li className="flex items-center gap-3 text-neutral-700 dark:text-neutral-300">
                            <span className="w-9 h-9 rounded-xl bg-brand-500/10 flex items-center justify-center shrink-0">
                                <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <a href={`mailto:${email}`} className="hover:text-brand-500 transition-colors">{email}</a>
                        </li>
                        <li className="flex items-center gap-3 text-neutral-700 dark:text-neutral-300">
                            <span className="w-9 h-9 rounded-xl bg-brand-500/10 flex items-center justify-center shrink-0">
                                <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </span>
                            <Link href={phoneHref} className="hover:text-brand-500 transition-colors">{phone}</Link>
                        </li>
                    </ul>
                </div>

                {/* Form */}
                <div className="bg-white dark:bg-neutral-950 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 reveal">
                    <form onSubmit={handleSubmit} noValidate>
                        <div className="grid sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label htmlFor="cf-name" className={labelClass}>
                                    {t('label_name', 'Full name')} <span className="text-brand-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="cf-name" name="name" required autoComplete="name"
                                    value={form.name} onChange={e => set('name', e.target.value)}
                                    className={inputClass} placeholder={t('placeholder_name', 'John Smith')} />
                            </div>
                            <div>
                                <label htmlFor="cf-contact" className={labelClass}>
                                    {t('label_contact', contactLabel)} <span className="text-brand-500" aria-hidden="true">*</span>
                                </label>
                                <input type="text" id="cf-contact" name="contact" required autoComplete="email"
                                    value={form.contact} onChange={e => set('contact', e.target.value)}
                                    className={inputClass} placeholder={t('placeholder_contact', contactPlaceholder)} />
                            </div>
                        </div>

                        <div className="mb-4">
                            <label htmlFor="cf-project-type" className={labelClass}>{t('label_project_type', 'Project type')}</label>
                            <select id="cf-project-type" name="project_type" value={form.project_type}
                                onChange={e => set('project_type', e.target.value)} className={inputClass}>
                                <option value="">– {chooseLabel} –</option>
                                {projectTypes.map(pt => (
                                    <option key={pt.value} value={pt.value}>
                                        {pt[`label_${locale}`] ?? pt.label_en}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="mb-6">
                            <label htmlFor="cf-message" className={labelClass}>
                                {t('label_message', 'Message')} <span className="text-brand-500" aria-hidden="true">*</span>
                            </label>
                            <textarea id="cf-message" name="message" required rows={4}
                                value={form.message} onChange={e => set('message', e.target.value)}
                                className={`${inputClass} resize-none`}
                                placeholder={t('placeholder_message', 'Tell us about your project or ask a question...')} />
                        </div>

                        <div className="mb-6">
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="gdpr_consent" checked={form.gdpr_consent}
                                    onChange={e => set('gdpr_consent', e.target.checked)} required
                                    className="mt-0.5 w-4 h-4 rounded border-neutral-300 text-brand-500 focus:ring-brand-500 shrink-0" />
                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    {t('gdpr_text', locale === 'pl'
                                        ? 'Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na zapytanie, zgodnie z'
                                        : 'I agree to the processing of my personal data for the purpose of responding to this enquiry, in accordance with the'
                                    )}{' '}
                                    <Link href={privacyUrl} className="text-brand-500 hover:underline">
                                        {t('gdpr_link_text', locale === 'pl' ? 'polityką prywatności' : 'privacy policy')}
                                    </Link>.
                                </span>
                            </label>
                        </div>

                        <button type="submit" disabled={status === 'sending'}
                            className="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed">
                            {status === 'sending'
                                ? (locale === 'pl' ? 'Wysyłanie…' : 'Sending…')
                                : t('submit_btn', locale === 'pl' ? 'Wyślij wiadomość' : 'Send message')}
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>

                        {status === 'success' && (
                            <p className="mt-4 text-sm text-center text-green-600 dark:text-green-400 font-medium">
                                {t('success_msg', "✓ Message sent! We'll be in touch shortly.")}
                            </p>
                        )}
                        {status === 'error' && (
                            <p className="mt-4 text-sm text-center text-red-600 dark:text-red-400 font-medium">
                                {t('error_msg', locale === 'pl' ? 'Wystąpił błąd. Sprawdź dane i spróbuj ponownie.' : 'Something went wrong. Please check your details and try again.')}
                            </p>
                        )}
                    </form>
                </div>
            </div>
        </section>
    );
}
