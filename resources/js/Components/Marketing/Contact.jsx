import { useState } from 'react';
import { router } from '@inertiajs/react';

const inputClass = 'w-full px-4 py-2.5 rounded-xl border border-neutral-200 dark:border-neutral-700 bg-neutral-50 dark:bg-neutral-900 text-neutral-900 dark:text-white placeholder-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 focus:border-transparent transition';
const labelClass = 'block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1.5';

export default function Contact() {
    const [form, setForm]       = useState({ name: '', company: '', email: '', phone: '', nip: '', project_type: '', contact_preference: '', message: '', gdpr_consent: false });
    const [status, setStatus]   = useState('idle'); // idle | success | error

    const set = (k, v) => setForm(prev => ({ ...prev, [k]: v }));

    const handleSubmit = async (e) => {
        e.preventDefault();
        if (!form.name || !form.email || !form.message || !form.gdpr_consent) {
            setStatus('error');
            return;
        }
        try {
            await fetch(route('contact.store'), {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '' },
                body: JSON.stringify(form),
            });
            setStatus('success');
        } catch {
            // TODO: wire up real API
            setStatus('success'); // optimistic for prototype
        }
    };

    return (
        <section id="kontakt" className="py-20 md:py-28 bg-neutral-50 dark:bg-neutral-900">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 grid lg:grid-cols-2 gap-12 lg:gap-20">

                {/* Info */}
                <div className="reveal">
                    <span className="section-label">Kontakt</span>
                    <h2 className="font-display text-3xl sm:text-4xl font-bold mt-3 mb-6 text-neutral-900 dark:text-white">
                        Porozmawiajmy<br />o Twoim projekcie
                    </h2>
                    <p className="text-neutral-600 dark:text-neutral-400 leading-relaxed mb-8">
                        Napisz do nas, a odezwiemy się w ciągu 24 godzin roboczych.
                        Pierwsze 30&nbsp;minut konsultacji jest bezpłatne.
                    </p>
                    <ul className="space-y-4">
                        <li className="flex items-center gap-3 text-neutral-700 dark:text-neutral-300">
                            <span className="w-9 h-9 rounded-xl bg-brand-500/10 flex items-center justify-center shrink-0">
                                <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </span>
                            <a href="mailto:hello@websiteexpert.co.uk" className="hover:text-brand-500 transition-colors">hello@websiteexpert.co.uk</a>
                        </li>
                        <li className="flex items-center gap-3 text-neutral-700 dark:text-neutral-300">
                            <span className="w-9 h-9 rounded-xl bg-brand-500/10 flex items-center justify-center shrink-0">
                                <svg className="w-4 h-4 text-brand-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </span>
                            <a href="tel:+44000000000" className="hover:text-brand-500 transition-colors">+44 000 000 000</a>
                        </li>
                    </ul>
                </div>

                {/* Form */}
                <div className="bg-white dark:bg-neutral-950 rounded-2xl border border-neutral-100 dark:border-neutral-800 p-6 sm:p-8 reveal">
                    <form onSubmit={handleSubmit} noValidate>
                        <div className="grid sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label htmlFor="cf-name" className={labelClass}>Imię i nazwisko <span className="text-brand-500" aria-hidden="true">*</span></label>
                                <input type="text" id="cf-name" name="name" required autoComplete="name" value={form.name} onChange={e => set('name', e.target.value)} className={inputClass} placeholder="Jan Kowalski" />
                            </div>
                            <div>
                                <label htmlFor="cf-company" className={labelClass}>Firma</label>
                                <input type="text" id="cf-company" name="company" autoComplete="organization" value={form.company} onChange={e => set('company', e.target.value)} className={inputClass} placeholder="Nazwa firmy" />
                            </div>
                        </div>

                        <div className="grid sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label htmlFor="cf-email" className={labelClass}>Email <span className="text-brand-500" aria-hidden="true">*</span></label>
                                <input type="email" id="cf-email" name="email" required autoComplete="email" value={form.email} onChange={e => set('email', e.target.value)} className={inputClass} placeholder="jan@firma.pl" />
                            </div>
                            <div>
                                <label htmlFor="cf-phone" className={labelClass}>Telefon</label>
                                <input type="tel" id="cf-phone" name="phone" autoComplete="tel" value={form.phone} onChange={e => set('phone', e.target.value)} className={inputClass} placeholder="+44 7700 000000" />
                            </div>
                        </div>

                        <div className="grid sm:grid-cols-2 gap-4 mb-4">
                            <div>
                                <label htmlFor="cf-nip" className={labelClass}>NIP / VAT</label>
                                <input type="text" id="cf-nip" name="nip" autoComplete="off" value={form.nip} onChange={e => set('nip', e.target.value)} className={inputClass} placeholder="000-000-00-00" />
                            </div>
                            <div>
                                <label htmlFor="cf-project-type" className={labelClass}>Rodzaj projektu</label>
                                <select id="cf-project-type" name="project_type" value={form.project_type} onChange={e => set('project_type', e.target.value)} className={inputClass}>
                                    <option value="">– Wybierz –</option>
                                    <option value="wizytowka">Strona wizytówkowa</option>
                                    <option value="ecommerce">Sklep e-commerce</option>
                                    <option value="aplikacja">Aplikacja webowa</option>
                                    <option value="seo">SEO / Pozycjonowanie</option>
                                    <option value="reklama">Kampania reklamowa</option>
                                    <option value="inne">Inne</option>
                                </select>
                            </div>
                        </div>

                        <div className="mb-4">
                            <label htmlFor="cf-contact-pref" className={labelClass}>Preferowany kontakt</label>
                            <select id="cf-contact-pref" name="contact_preference" value={form.contact_preference} onChange={e => set('contact_preference', e.target.value)} className={inputClass}>
                                <option value="">– Dowolna forma –</option>
                                <option value="email">Email</option>
                                <option value="telefon">Telefon</option>
                                <option value="teams">Microsoft Teams</option>
                                <option value="meet">Google Meet</option>
                            </select>
                        </div>

                        <div className="mb-6">
                            <label htmlFor="cf-message" className={labelClass}>Wiadomość <span className="text-brand-500" aria-hidden="true">*</span></label>
                            <textarea id="cf-message" name="message" required rows={4} value={form.message} onChange={e => set('message', e.target.value)} className={`${inputClass} resize-none`} placeholder="Opisz swój projekt lub pytanie..." />
                        </div>

                        <div className="mb-6">
                            <label className="flex items-start gap-3 cursor-pointer">
                                <input type="checkbox" name="gdpr_consent" checked={form.gdpr_consent} onChange={e => set('gdpr_consent', e.target.checked)} required className="mt-0.5 w-4 h-4 rounded border-neutral-300 text-brand-500 focus:ring-brand-500 shrink-0" />
                                <span className="text-xs text-neutral-500 dark:text-neutral-400">
                                    Wyrażam zgodę na przetwarzanie moich danych osobowych w celu odpowiedzi na zapytanie, zgodnie z{' '}
                                    <a href="#" className="text-brand-500 hover:underline">polityką prywatności</a>.
                                </span>
                            </label>
                        </div>

                        <button type="submit" className="w-full flex items-center justify-center gap-2 px-6 py-3.5 rounded-xl bg-brand-500 text-white font-semibold hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20">
                            Wyślij wiadomość
                            <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5" aria-hidden="true">
                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                            </svg>
                        </button>

                        {status === 'success' && (
                            <p className="mt-4 text-sm text-center text-green-600 dark:text-green-400 font-medium">✓ Wiadomość wysłana! Odezwiemy się wkrótce.</p>
                        )}
                        {status === 'error' && (
                            <p className="mt-4 text-sm text-center text-red-600 dark:text-red-400 font-medium">Uzupełnij wymagane pola i zaznacz zgodę na przetwarzanie danych.</p>
                        )}
                    </form>
                </div>
            </div>
        </section>
    );
}
