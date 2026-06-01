import { Head, Link, useForm, usePage } from '@inertiajs/react';
import MarketingLayout from '@/Layouts/MarketingLayout';

const COUNTRIES = [
    { code: 'GB', name: 'United Kingdom' },
    { code: 'IE', name: 'Ireland' },
    { code: 'US', name: 'United States' },
    { code: 'AU', name: 'Australia' },
    { code: 'CA', name: 'Canada' },
    { code: 'DE', name: 'Germany' },
    { code: 'FR', name: 'France' },
    { code: 'PL', name: 'Poland' },
];

function FieldError({ error }) {
    if (!error) return null;
    return <p className="mt-1 text-xs text-red-500">{error}</p>;
}

function FormField({ label, error, required, children }) {
    return (
        <div>
            <label className="block text-sm font-medium text-neutral-700 dark:text-neutral-300 mb-1">
                {label}{required && <span className="text-red-500 ml-1">*</span>}
            </label>
            {children}
            <FieldError error={error} />
        </div>
    );
}

const INPUT_CLS = 'w-full rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-neutral-900 px-4 py-2.5 text-sm text-neutral-900 dark:text-white placeholder:text-neutral-400 focus:outline-none focus:ring-2 focus:ring-brand-500 transition';

export default function DomainsOrder({ domain_name, tld, full_domain, action, prices, prefill = {}, auth }) {
    const { footer } = usePage().props;
    const symbol = '£';

    const { data, setData, post, processing, errors } = useForm({
        domain_name:    domain_name,
        tld:            tld,
        action:         action,
        years:          1,
        first_name:     prefill.first_name    ?? '',
        last_name:      prefill.last_name     ?? '',
        email:          prefill.email         ?? '',
        phone:          prefill.phone         ?? '',
        organisation:   prefill.organisation  ?? '',
        address_line1:  prefill.address_line1 ?? '',
        address_line2:  prefill.address_line2 ?? '',
        city:           prefill.city          ?? '',
        county:         prefill.county        ?? '',
        postcode:       prefill.postcode      ?? '',
        country_code:   prefill.country_code  ?? 'GB',
        notes:          '',
    });

    const currentPrice = prices[data.years] ?? 0;

    function handleSubmit(e) {
        e.preventDefault();
        post(route('domains.order.store'));
    }

    return (
        <MarketingLayout auth={auth} footer={footer}>
            <Head title={`${action === 'transfer' ? 'Transfer' : action === 'renew' ? 'Renew' : 'Register'} ${full_domain}`} />

            {/* Page header */}
            <section className="relative overflow-hidden border-b border-neutral-100 dark:border-neutral-800 pt-24 pb-10">
                <div className="absolute inset-0 bg-gradient-to-br from-neutral-50 via-white to-brand-50/30 dark:from-neutral-950 dark:via-neutral-900 dark:to-neutral-950" aria-hidden="true" />
                <div className="relative mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
                    <Link
                        href={route('domains.check')}
                        className="inline-flex items-center gap-1.5 text-sm text-neutral-500 dark:text-neutral-400 hover:text-neutral-700 dark:hover:text-white transition-colors mb-4"
                    >
                        <svg className="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2.5"><path strokeLinecap="round" strokeLinejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        Back to search
                    </Link>
                    <h1 className="text-2xl font-bold text-neutral-900 dark:text-white">
                        {action === 'transfer' ? 'Transfer' : action === 'renew' ? 'Renew' : 'Register'} Domain
                    </h1>
                    <p className="text-neutral-500 dark:text-neutral-400 mt-1 text-lg font-mono">{full_domain}</p>
                </div>
            </section>

            <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-10">
                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Registration period */}
                    <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6 space-y-4">
                        <h2 className="font-semibold text-neutral-900 dark:text-white">Registration Options</h2>

                        <FormField label="Registration Period" error={errors.years} required>
                            <select
                                value={data.years}
                                onChange={e => setData('years', parseInt(e.target.value))}
                                className={INPUT_CLS}
                            >
                                {[1, 2, 3, 5].map(y => (
                                    <option key={y} value={y}>
                                        {y} {y === 1 ? 'year' : 'years'} — {symbol}{(prices[y] ?? 0).toFixed(2)}
                                    </option>
                                ))}
                            </select>
                        </FormField>

                        <div className="rounded-xl bg-brand-50 dark:bg-brand-500/10 border border-brand-100 dark:border-brand-500/20 px-4 py-3 flex justify-between items-center">
                            <span className="text-sm text-neutral-600 dark:text-neutral-300">Total Price</span>
                            <span className="text-xl font-bold text-neutral-900 dark:text-white">{symbol}{currentPrice.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* Registrant details */}
                    <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6 space-y-4">
                        <div>
                            <h2 className="font-semibold text-neutral-900 dark:text-white">Registrant Details (WHOIS)</h2>
                            <p className="text-xs text-neutral-500 dark:text-neutral-400 mt-1">Used for domain registration. Free WHOIS privacy is included.</p>
                        </div>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField label="First Name" error={errors.first_name} required>
                                <input type="text" value={data.first_name} onChange={e => setData('first_name', e.target.value)} className={INPUT_CLS} />
                            </FormField>
                            <FormField label="Last Name" error={errors.last_name} required>
                                <input type="text" value={data.last_name} onChange={e => setData('last_name', e.target.value)} className={INPUT_CLS} />
                            </FormField>
                            <FormField label="Email Address" error={errors.email} required>
                                <input type="email" value={data.email} onChange={e => setData('email', e.target.value)} className={INPUT_CLS} />
                            </FormField>
                            <FormField label="Phone" error={errors.phone}>
                                <input type="tel" value={data.phone} onChange={e => setData('phone', e.target.value)} className={INPUT_CLS} placeholder="Optional" />
                            </FormField>
                            <FormField label="Organisation / Company" error={errors.organisation}>
                                <input type="text" value={data.organisation} onChange={e => setData('organisation', e.target.value)} className={INPUT_CLS} placeholder="Optional" />
                            </FormField>
                        </div>

                        <div className="pt-2 border-t border-neutral-100 dark:border-neutral-800 space-y-4">
                            <FormField label="Address Line 1" error={errors.address_line1} required>
                                <input type="text" value={data.address_line1} onChange={e => setData('address_line1', e.target.value)} className={INPUT_CLS} />
                            </FormField>
                            <FormField label="Address Line 2" error={errors.address_line2}>
                                <input type="text" value={data.address_line2} onChange={e => setData('address_line2', e.target.value)} className={INPUT_CLS} placeholder="Optional" />
                            </FormField>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <FormField label="City" error={errors.city} required>
                                    <input type="text" value={data.city} onChange={e => setData('city', e.target.value)} className={INPUT_CLS} />
                                </FormField>
                                <FormField label="County" error={errors.county}>
                                    <input type="text" value={data.county} onChange={e => setData('county', e.target.value)} className={INPUT_CLS} placeholder="Optional" />
                                </FormField>
                                <FormField label="Postcode" error={errors.postcode} required>
                                    <input type="text" value={data.postcode} onChange={e => setData('postcode', e.target.value)} className={INPUT_CLS} />
                                </FormField>
                            </div>

                            <FormField label="Country" error={errors.country_code} required>
                                <select value={data.country_code} onChange={e => setData('country_code', e.target.value)} className={INPUT_CLS}>
                                    {COUNTRIES.map(c => (
                                        <option key={c.code} value={c.code}>{c.name}</option>
                                    ))}
                                </select>
                            </FormField>
                        </div>
                    </div>

                    {/* Notes */}
                    <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6 space-y-3">
                        <h2 className="font-semibold text-neutral-900 dark:text-white">
                            Notes <span className="text-neutral-400 font-normal">(optional)</span>
                        </h2>
                        <textarea
                            value={data.notes}
                            onChange={e => setData('notes', e.target.value)}
                            rows={3}
                            className={INPUT_CLS}
                            placeholder="Any special instructions…"
                        />
                    </div>

                    {/* Summary + submit */}
                    <div className="bg-white dark:bg-neutral-900 rounded-2xl border border-neutral-200 dark:border-neutral-800 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div className="text-sm text-neutral-500 dark:text-neutral-400">Order Summary</div>
                            <div className="font-bold text-neutral-900 dark:text-white text-lg font-mono">{full_domain}</div>
                            <div className="text-sm text-neutral-600 dark:text-neutral-300 capitalize">
                                {action} · {data.years} {data.years === 1 ? 'year' : 'years'}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="text-2xl font-black text-neutral-900 dark:text-white">{symbol}{currentPrice.toFixed(2)}</span>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-brand-500 px-6 py-3 font-bold text-white hover:bg-brand-600 active:scale-95 transition-all shadow-lg shadow-brand-500/20 disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Processing…' : 'Continue to Payment →'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </MarketingLayout>
    );
}
