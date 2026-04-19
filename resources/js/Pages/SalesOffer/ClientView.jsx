import { useState } from 'react';
import { Head } from '@inertiajs/react';
import axios from 'axios';
import DOMPurify from 'dompurify';
import { marked } from 'marked';
import { useGoogleReCaptcha } from 'react-google-recaptcha-v3';

function renderMarkdown(raw) {
    if (!raw) return '';
    const dirty = marked.parse(raw, { breaks: true });
    return DOMPurify.sanitize(dirty);
}

export default function ClientView({ offer, business, client }) {
    const [ctaState, setCtaState] = useState(offer?.cta_accepted ? 'done' : 'idle');
    const { executeRecaptcha } = useGoogleReCaptcha();

    const lang = offer?.language ?? 'en';
    const isPl = lang === 'pl';

    const t = {
        tagline:    isPl ? `Oferta przygotowana dla: ${client?.company || client?.name || ''}` : `Offer prepared for: ${client?.company || client?.name || ''}`,
        ctaLabel:   isPl ? 'Chcę to omówić — skontaktuj się ze mną' : 'I want to discuss this — contact me',
        ctaConfirm: isPl ? 'Dziękujemy! Wkrótce się odezwiemy.' : "Thank you! We'll be in touch shortly.",
        ctaError:   isPl ? 'Coś poszło nie tak. Spróbuj ponownie.' : 'Something went wrong. Please try again.',
        readingLabel: isPl ? 'Szacowany czas czytania: ~5 min' : 'Estimated reading time: ~5 min',
    };

    async function handleCta() {
        if (ctaState !== 'idle') return;
        setCtaState('loading');
        try {
            const recaptchaToken = executeRecaptcha
                ? await executeRecaptcha('sales_offer_cta')
                : null;
            await axios.post(`/offers/${offer.token}/accept`, {
                recaptcha_token: recaptchaToken,
            });
            setCtaState('done');
        } catch {
            setCtaState('error');
        }
    }

    return (
        <>
            <Head title={offer?.title ?? 'Sales Offer'} />

            <div className="min-h-screen bg-gradient-to-br from-sky-50 via-white to-indigo-50">
                {/* Header */}
                <header className="border-b border-sky-100 bg-white/80 backdrop-blur-sm sticky top-0 z-10">
                    <div className="mx-auto max-w-3xl px-4 py-4 flex items-center justify-between">
                        <div>
                            <p className="text-lg font-bold text-sky-700">{business?.name ?? 'Website Expert'}</p>
                            <p className="text-xs text-gray-500">{t.tagline}</p>
                        </div>
                        <span className="hidden sm:block text-xs text-gray-400">{t.readingLabel}</span>
                    </div>
                </header>

                {/* Main content */}
                <main className="mx-auto max-w-3xl px-4 py-10">
                    {/* Title card */}
                    <div className="rounded-2xl bg-gradient-to-r from-sky-600 to-indigo-600 p-8 text-white shadow-lg mb-8">
                        <h1 className="text-2xl font-bold leading-tight">{offer?.title}</h1>
                        {offer?.sent_at && (
                            <p className="mt-2 text-sky-100 text-sm">
                                {isPl ? 'Data wysłania' : 'Sent on'}: {new Date(offer.sent_at).toLocaleDateString(lang === 'pl' ? 'pl-PL' : 'en-GB', { year: 'numeric', month: 'long', day: 'numeric' })}
                            </p>
                        )}
                    </div>

                    {/* Offer body — sanitized markdown */}
                    <article
                        className="prose prose-sky max-w-none rounded-2xl bg-white p-8 shadow-sm
                                   prose-headings:text-sky-800 prose-h2:border-b prose-h2:border-sky-100 prose-h2:pb-2
                                   prose-blockquote:border-sky-400 prose-blockquote:text-gray-600
                                   prose-li:marker:text-sky-500"
                        dangerouslySetInnerHTML={{ __html: renderMarkdown(offer?.body) }}
                    />

                    {/* CTA */}
                    <div className="mt-10 rounded-2xl bg-white border border-sky-100 p-8 shadow-sm text-center">
                        {ctaState === 'done' ? (
                            <div className="text-green-700 font-medium">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-6 h-6 mx-auto mb-2 text-green-500">
                                    <path fillRule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clipRule="evenodd" />
                                </svg>
                                {t.ctaConfirm}
                            </div>
                        ) : ctaState === 'error' ? (
                            <div className="space-y-3">
                                <p className="text-red-600 text-sm">{t.ctaError}</p>
                                <button
                                    type="button"
                                    onClick={() => setCtaState('idle')}
                                    className="inline-flex items-center gap-2 rounded-xl border border-sky-300 px-5 py-2.5 text-sky-700 font-medium text-sm hover:bg-sky-50 transition-colors"
                                >
                                    {isPl ? 'Spróbuj ponownie' : 'Try again'}
                                </button>
                            </div>
                        ) : (
                            <>
                                <p className="text-gray-600 mb-4 text-sm">
                                    {isPl
                                        ? 'Podoba Ci się ta oferta? Daj nam znać, a umówimy rozmowę.'
                                        : "Like what you see? Let us know and we'll schedule a call."}
                                </p>
                                <button
                                    type="button"
                                    onClick={handleCta}
                                    disabled={ctaState === 'loading'}
                                    className="inline-flex items-center gap-2 rounded-xl bg-sky-600 px-6 py-3 text-white font-semibold text-sm shadow hover:bg-sky-700 focus:outline-none focus:ring-2 focus:ring-sky-500 focus:ring-offset-2 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {ctaState === 'loading' ? (
                                        <svg className="animate-spin w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"/>
                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                                        </svg>
                                    ) : (
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" className="w-4 h-4">
                                            <path d="M3 4a2 2 0 0 0-2 2v1.161l8.441 4.221a1.25 1.25 0 0 0 1.118 0L19 7.162V6a2 2 0 0 0-2-2H3Z" />
                                            <path d="m19 8.839-7.77 3.885a2.75 2.75 0 0 1-2.46 0L1 8.839V14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V8.839Z" />
                                        </svg>
                                    )}
                                    {t.ctaLabel}
                                </button>
                            </>
                        )}
                    </div>
                </main>

                <footer className="mt-16 border-t border-gray-100 py-6 text-center text-xs text-gray-400">
                    &copy; {new Date().getFullYear()} {business?.name ?? 'Website Expert'}. {isPl ? 'Wszelkie prawa zastrzeżone.' : 'All rights reserved.'}
                </footer>
            </div>
        </>
    );
}
