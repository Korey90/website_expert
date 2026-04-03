import { useState } from 'react';
import axios from 'axios';
import SectionHeading from '@/Components/LandingPage/PublicSection/SectionHeading';
import SectionShell from '@/Components/LandingPage/PublicSection/SectionShell';
import useLandingPageTrans from '@/Hooks/useLandingPageTrans';

const STATES = { idle: 'idle', sending: 'sending', success: 'success', error: 'error' };
const DEFAULT_FIELDS = ['name', 'email', 'phone', 'message'];
const EMAIL_PATTERN = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

/**
 * Lead capture form section — AJAX submit, honeypot, CSRF via axios cookie.
 *
 * Props:
 *   content.headline        – form title
 *   content.subheadline     – optional subtitle
 *   content.fields          – array: ['name','email','phone','message'] visible fields, default all
 *   content.required        – array: required field keys, default ['email']
 *   content.cta_text        – button label, default 'Send'
 *   content.success_message – shown after submit
 *   content.redirect_url    – optional post-submit redirect
 *   slug                    – landing page slug (for route)
 */
export default function FormSection({ content = {}, settings = {}, slug }) {
    const t = useLandingPageTrans();
    const fields = content.fields ?? DEFAULT_FIELDS;
    const required = content.required ?? ['email'];

    const [state, setState] = useState(STATES.idle);
    const [formData, setFormData] = useState({ name: '', email: '', phone: '', message: '' });
    const [errors, setErrors] = useState({});
    const [submitMessage, setSubmitMessage] = useState('');

    const panelClass = settings.background === 'dark'
        ? 'border-white/10 bg-white/5'
        : 'border-gray-200 bg-white shadow-xl dark:border-gray-800 dark:bg-gray-900';

    const LABELS = {
        name: t('form_name'),
        email: t('form_email'),
        phone: t('form_phone'),
        message: t('form_message'),
    };

    const handleChange = (e) => {
        setFormData((prev) => ({ ...prev, [e.target.name]: e.target.value }));
        if (state === STATES.error) {
            setState(STATES.idle);
            setSubmitMessage('');
        }
        if (errors[e.target.name]) {
            setErrors((prev) => ({ ...prev, [e.target.name]: null }));
        }
    };

    const handleSubmit = async (e) => {
        e.preventDefault();

        // Honeypot check — if website field has a value, silently ignore
        const honeypot = e.target.elements['website']?.value;
        if (honeypot) {
            setSubmitMessage(content.success_message ?? t('form_success_default'));
            setState(STATES.success);
            return;
        }

        // Basic client-side validation
        const newErrors = {};
        for (const field of required) {
            if (!formData[field]?.trim()) {
                newErrors[field] = `${LABELS[field] ?? field}: ${t('required_field')}`;
            }
        }
        if (formData.email && !EMAIL_PATTERN.test(formData.email)) {
            newErrors.email = t('form_email_invalid');
        }
        if (Object.keys(newErrors).length > 0) {
            setErrors(newErrors);
            return;
        }

        setState(STATES.sending);
        setSubmitMessage('');
        try {
            const payload = { landing_page_slug: slug };
            for (const field of fields) {
                payload[field] = formData[field] ?? '';
            }

            const response = await axios.post('/leads', payload);

            setSubmitMessage(response.data?.message ?? content.success_message ?? t('form_success_default'));
            setState(STATES.success);

            const redirectUrl = response.data?.redirect_url ?? content.redirect_url;
            if (redirectUrl) {
                setTimeout(() => {
                    window.location.href = redirectUrl;
                }, 2000);
            }
        } catch (err) {
            if (err.response?.status === 422) {
                const serverErrors = err.response.data.errors ?? {};
                const mapped = {};
                for (const [key, msgs] of Object.entries(serverErrors)) {
                    mapped[key] = Array.isArray(msgs) ? msgs[0] : msgs;
                }
                setErrors(mapped);
                setSubmitMessage(err.response.data?.message ?? '');
                setState(STATES.idle);
            } else {
                setSubmitMessage(err.response?.data?.message ?? t('form_error_default'));
                setState(STATES.error);
            }
        }
    };

    if (state === STATES.success) {
        return (
            <SectionShell settings={settings} backgroundFallback="white" width="narrow">
                <div className="flex flex-col items-center gap-4 text-center">
                    <span className="flex h-16 w-16 items-center justify-center rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400">
                        <svg className="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                    </span>
                    <h3 className="font-display text-2xl font-bold text-gray-900 dark:text-white">
                        {submitMessage || content.success_message || t('form_success_default')}
                    </h3>
                </div>
            </SectionShell>
        );
    }

    return (
        <SectionShell settings={settings} backgroundFallback="white" width="narrow" id="form">
            <div className={[ 'rounded-[2rem] border p-6 sm:p-8 lg:p-10', panelClass ].join(' ')}>
                <SectionHeading
                    title={content.headline}
                    subtitle={content.subheadline}
                    align="center"
                    className="mb-8"
                />

                {state === STATES.error && (
                    <div className="mb-6 rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-400 p-4 text-sm">
                        {submitMessage || t('form_error_default')}
                    </div>
                )}

                <form onSubmit={handleSubmit} noValidate className="flex flex-col gap-4 sm:gap-5">
                    {/* Honeypot — bots fill this, humans don't see it */}
                    <input
                        type="text"
                        name="website"
                        defaultValue=""
                        tabIndex={-1}
                        autoComplete="off"
                        aria-label={t('honeypot_label')}
                        aria-hidden="true"
                        className="hidden"
                    />

                    {fields.includes('name') && (
                        <div>
                            <label htmlFor="lp-name" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {LABELS.name}{required.includes('name') && ' *'}
                            </label>
                            <input
                                id="lp-name"
                                type="text"
                                name="name"
                                value={formData.name}
                                onChange={handleChange}
                                autoComplete="name"
                                className={`block w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition ${errors.name ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}`}
                            />
                            {errors.name && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors.name}</p>}
                        </div>
                    )}

                    {fields.includes('email') && (
                        <div>
                            <label htmlFor="lp-email" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {LABELS.email}{required.includes('email') && ' *'}
                            </label>
                            <input
                                id="lp-email"
                                type="email"
                                name="email"
                                value={formData.email}
                                onChange={handleChange}
                                autoComplete="email"
                                className={`block w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition ${errors.email ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}`}
                            />
                            {errors.email && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors.email}</p>}
                        </div>
                    )}

                    {fields.includes('phone') && (
                        <div>
                            <label htmlFor="lp-phone" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {LABELS.phone}{required.includes('phone') && ' *'}
                            </label>
                            <input
                                id="lp-phone"
                                type="tel"
                                name="phone"
                                value={formData.phone}
                                onChange={handleChange}
                                autoComplete="tel"
                                className={`block w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition ${errors.phone ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}`}
                            />
                            {errors.phone && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors.phone}</p>}
                        </div>
                    )}

                    {fields.includes('message') && (
                        <div>
                            <label htmlFor="lp-message" className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                {LABELS.message}{required.includes('message') && ' *'}
                            </label>
                            <textarea
                                id="lp-message"
                                name="message"
                                rows={4}
                                value={formData.message}
                                onChange={handleChange}
                                className={`block w-full rounded-xl border px-4 py-3 text-sm bg-white dark:bg-gray-800 text-gray-900 dark:text-white placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition resize-none ${errors.message ? 'border-red-500' : 'border-gray-300 dark:border-gray-600'}`}
                            />
                            {errors.message && <p className="mt-1 text-xs text-red-600 dark:text-red-400">{errors.message}</p>}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={state === STATES.sending}
                        className="flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 px-8 py-4 text-base font-bold text-white shadow-lg transition hover:bg-brand-700 disabled:opacity-60 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:ring-offset-2"
                    >
                        {state === STATES.sending ? (
                            <>
                                <svg className="h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24">
                                    <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                    <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" />
                                </svg>
                                {t('form_sending')}
                            </>
                        ) : (
                            content.cta_text ?? t('form_submit')
                        )}
                    </button>
                </form>
            </div>
        </SectionShell>
    );
}
