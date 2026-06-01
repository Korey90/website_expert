import { Head, Link, useForm } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';

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
    return <p className="mt-1 text-xs text-red-600">{error}</p>;
}

function FormField({ label, error, required, children }) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">
                {label}{required && <span className="text-red-500 ml-1">*</span>}
            </label>
            {children}
            <FieldError error={error} />
        </div>
    );
}

export default function DomainsOrder({ client, domain_name, tld, full_domain, action, prices, prefill = {} }) {
    const symbol = '£';

    const { data, setData, post, processing, errors } = useForm({
        domain_name:    domain_name,
        tld:            tld,
        action:         action,
        years:          1,
        // Contact (WHOIS registrant)
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
        post(route('portal.domains.order.store'));
    }

    return (
        <PortalLayout client={client}>
            <Head title={`Register ${full_domain}`} />

            <div className="max-w-3xl mx-auto space-y-6">
                {/* Header */}
                <div>
                    <Link href={route('domains.check')} className="text-sm text-gray-500 hover:text-gray-700 inline-block mb-1">
                        ← Back to search
                    </Link>
                    <h1 className="text-2xl font-bold text-gray-900">
                        {action === 'transfer' ? 'Transfer' : action === 'renew' ? 'Renew' : 'Register'} Domain
                    </h1>
                    <p className="text-gray-500 mt-1 text-lg font-mono">{full_domain}</p>
                </div>

                <form onSubmit={handleSubmit} className="space-y-6">
                    {/* Registration options */}
                    <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <h2 className="font-semibold text-gray-900">Registration Options</h2>

                        <FormField label="Registration Period" error={errors.years} required>
                            <select
                                value={data.years}
                                onChange={e => setData('years', parseInt(e.target.value))}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                            >
                                {[1, 2, 3, 5].map(y => (
                                    <option key={y} value={y}>
                                        {y} {y === 1 ? 'year' : 'years'} — {symbol}{(prices[y] ?? 0).toFixed(2)}
                                    </option>
                                ))}
                            </select>
                        </FormField>

                        <div className="rounded-lg bg-gray-50 border border-gray-200 px-4 py-3 flex justify-between items-center">
                            <span className="text-sm text-gray-600">Total Price</span>
                            <span className="text-xl font-bold text-gray-900">{symbol}{currentPrice.toFixed(2)}</span>
                        </div>
                    </div>

                    {/* WHOIS / Registrant Details */}
                    <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <h2 className="font-semibold text-gray-900">Registrant Details (WHOIS)</h2>
                        <p className="text-xs text-gray-500">These details will be used for domain registration. WHOIS privacy is included at no extra cost.</p>

                        <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <FormField label="First Name" error={errors.first_name} required>
                                <input
                                    type="text"
                                    value={data.first_name}
                                    onChange={e => setData('first_name', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                            </FormField>

                            <FormField label="Last Name" error={errors.last_name} required>
                                <input
                                    type="text"
                                    value={data.last_name}
                                    onChange={e => setData('last_name', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                            </FormField>

                            <FormField label="Email Address" error={errors.email} required>
                                <input
                                    type="email"
                                    value={data.email}
                                    onChange={e => setData('email', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                            </FormField>

                            <FormField label="Phone" error={errors.phone}>
                                <input
                                    type="tel"
                                    value={data.phone}
                                    onChange={e => setData('phone', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                            </FormField>

                            <FormField label="Organisation / Company" error={errors.organisation}>
                                <input
                                    type="text"
                                    value={data.organisation}
                                    onChange={e => setData('organisation', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    placeholder="Optional"
                                />
                            </FormField>
                        </div>

                        <div className="pt-2 border-t border-gray-100 space-y-4">
                            <FormField label="Address Line 1" error={errors.address_line1} required>
                                <input
                                    type="text"
                                    value={data.address_line1}
                                    onChange={e => setData('address_line1', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                />
                            </FormField>

                            <FormField label="Address Line 2" error={errors.address_line2}>
                                <input
                                    type="text"
                                    value={data.address_line2}
                                    onChange={e => setData('address_line2', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    placeholder="Optional"
                                />
                            </FormField>

                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                <FormField label="City" error={errors.city} required>
                                    <input
                                        type="text"
                                        value={data.city}
                                        onChange={e => setData('city', e.target.value)}
                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    />
                                </FormField>

                                <FormField label="County" error={errors.county}>
                                    <input
                                        type="text"
                                        value={data.county}
                                        onChange={e => setData('county', e.target.value)}
                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                        placeholder="Optional"
                                    />
                                </FormField>

                                <FormField label="Postcode" error={errors.postcode} required>
                                    <input
                                        type="text"
                                        value={data.postcode}
                                        onChange={e => setData('postcode', e.target.value)}
                                        className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                    />
                                </FormField>
                            </div>

                            <FormField label="Country" error={errors.country_code} required>
                                <select
                                    value={data.country_code}
                                    onChange={e => setData('country_code', e.target.value)}
                                    className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                                >
                                    {COUNTRIES.map(c => (
                                        <option key={c.code} value={c.code}>{c.name}</option>
                                    ))}
                                </select>
                            </FormField>
                        </div>
                    </div>

                    {/* Notes */}
                    <div className="bg-white rounded-xl border border-gray-200 p-6 space-y-4">
                        <h2 className="font-semibold text-gray-900">Additional Notes <span className="text-gray-400 font-normal">(optional)</span></h2>
                        <textarea
                            value={data.notes}
                            onChange={e => setData('notes', e.target.value)}
                            rows={3}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-red-500"
                            placeholder="Any special instructions or notes for us…"
                        />
                    </div>

                    {/* Order summary + submit */}
                    <div className="bg-white rounded-xl border border-gray-200 p-6 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                        <div>
                            <div className="text-sm text-gray-500">Order Summary</div>
                            <div className="font-bold text-gray-900 text-lg">{full_domain}</div>
                            <div className="text-sm text-gray-600">
                                {action} · {data.years} {data.years === 1 ? 'year' : 'years'}
                            </div>
                        </div>
                        <div className="flex items-center gap-4">
                            <span className="text-2xl font-black text-gray-900">{symbol}{currentPrice.toFixed(2)}</span>
                            <button
                                type="submit"
                                disabled={processing}
                                className="rounded-xl bg-red-600 px-6 py-3 font-bold text-white hover:bg-red-700 transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                            >
                                {processing ? 'Processing…' : 'Continue to Payment →'}
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </PortalLayout>
    );
}
