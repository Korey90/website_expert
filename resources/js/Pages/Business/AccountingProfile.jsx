import { Head, Link, useForm, usePage } from '@inertiajs/react';
import InputError from '@/Components/InputError';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import TextInput from '@/Components/TextInput';
import PortalLayout from '@/Layouts/PortalLayout';

const selectClass = 'mt-1 block w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-red-500 focus:ring-red-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white';

function Section({ title, description, children }) {
    return (
        <section className="space-y-5 rounded-xl border border-gray-200 bg-white p-6 dark:border-gray-700 dark:bg-gray-800">
            <div>
                <h2 className="text-base font-semibold text-gray-900 dark:text-white">{title}</h2>
                <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">{description}</p>
            </div>
            {children}
        </section>
    );
}

function Field({ id, label, error, hint, children }) {
    return (
        <div>
            <InputLabel htmlFor={id} value={label} />
            {children}
            {hint && <p className="mt-1 text-xs text-gray-500 dark:text-gray-400">{hint}</p>}
            <InputError message={error} className="mt-1" />
        </div>
    );
}

export default function AccountingProfile({ business, client, profile }) {
    const { flash, finance_translations: translations } = usePage().props;
    const t = translations.accounting;
    const fields = t.fields;
    const options = t.options;
    const sections = t.sections;
    const address = profile.registered_address ?? {};

    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        entity_type: profile.entity_type ?? 'limited_company',
        legal_name: profile.legal_name ?? business.name ?? '',
        company_number: profile.company_number ?? '',
        company_utr: '',
        registered_address: {
            line_1: address.line_1 ?? '',
            line_2: address.line_2 ?? '',
            city: address.city ?? '',
            county: address.county ?? '',
            postcode: address.postcode ?? '',
            country: address.country ?? 'GB',
        },
        functional_currency: profile.functional_currency ?? 'GBP',
        accounting_basis: profile.accounting_basis ?? 'accrual',
        financial_year_start: profile.financial_year_start ?? '',
        financial_year_end: profile.financial_year_end ?? '',
        vat_registered: Boolean(profile.vat_registered),
        vat_number: profile.vat_number ?? '',
        vat_scheme: profile.vat_scheme ?? '',
        vat_effective_from: profile.vat_effective_from ?? '',
        vat_effective_to: profile.vat_effective_to ?? '',
        paye_reference: '',
        accounts_office_reference: '',
    });

    const updateAddress = (key, value) => {
        setData('registered_address', { ...data.registered_address, [key]: value });
    };

    const secretHint = (saved, extra = null) => [
        saved ? t.hints.secret_saved : null,
        t.hints.secret_blank,
        extra,
    ].filter(Boolean).join(' ');

    const submit = (event) => {
        event.preventDefault();
        patch(route('business.accounting.update'), { preserveScroll: true });
    };

    return (
        <PortalLayout client={client}>
            <Head title={t.title} />

            <div className="mx-auto max-w-4xl space-y-6">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">{t.title}</h1>
                        <p className="mt-1 max-w-2xl text-sm text-gray-600 dark:text-gray-400">{t.subtitle}</p>
                    </div>
                    <Link href={route('business.edit')} className="shrink-0 text-sm font-medium text-red-600 hover:underline dark:text-red-400">
                        {t.back_to_settings} →
                    </Link>
                </div>

                {(flash?.success || recentlySuccessful) && (
                    <div className="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700 dark:border-green-700 dark:bg-green-900/20 dark:text-green-300">
                        {flash?.success ?? t.saved}
                    </div>
                )}

                <form onSubmit={submit} className="space-y-6">
                    <Section title={sections.entity} description={sections.entity_description}>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="entity_type" label={fields.entity_type} error={errors.entity_type}>
                                <select id="entity_type" value={data.entity_type} onChange={(event) => setData('entity_type', event.target.value)} className={selectClass}>
                                    <option value="limited_company">{options.limited_company}</option>
                                    <option value="sole_trader">{options.sole_trader}</option>
                                    <option value="partnership">{options.partnership}</option>
                                </select>
                            </Field>
                            <Field id="legal_name" label={fields.legal_name} error={errors.legal_name}>
                                <TextInput id="legal_name" value={data.legal_name} onChange={(event) => setData('legal_name', event.target.value)} className="mt-1 block w-full" required maxLength={255} />
                            </Field>
                            {data.entity_type === 'limited_company' && (
                                <Field id="company_number" label={fields.company_number} error={errors.company_number}>
                                    <TextInput id="company_number" value={data.company_number} onChange={(event) => setData('company_number', event.target.value.toUpperCase())} className="mt-1 block w-full" required maxLength={20} />
                                </Field>
                            )}
                            <Field id="company_utr" label={fields.company_utr} error={errors.company_utr} hint={secretHint(profile.has_company_utr, t.hints.utr)}>
                                <TextInput id="company_utr" type="password" value={data.company_utr} onChange={(event) => setData('company_utr', event.target.value)} className="mt-1 block w-full" inputMode="numeric" autoComplete="new-password" maxLength={14} />
                            </Field>
                        </div>
                    </Section>

                    <Section title={sections.address} description={sections.address_description}>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="address_line_1" label={fields.line_1} error={errors['registered_address.line_1']}>
                                <TextInput id="address_line_1" value={data.registered_address.line_1} onChange={(event) => updateAddress('line_1', event.target.value)} className="mt-1 block w-full" required />
                            </Field>
                            <Field id="address_line_2" label={fields.line_2} error={errors['registered_address.line_2']}>
                                <TextInput id="address_line_2" value={data.registered_address.line_2} onChange={(event) => updateAddress('line_2', event.target.value)} className="mt-1 block w-full" />
                            </Field>
                            <Field id="address_city" label={fields.city} error={errors['registered_address.city']}>
                                <TextInput id="address_city" value={data.registered_address.city} onChange={(event) => updateAddress('city', event.target.value)} className="mt-1 block w-full" required />
                            </Field>
                            <Field id="address_county" label={fields.county} error={errors['registered_address.county']}>
                                <TextInput id="address_county" value={data.registered_address.county} onChange={(event) => updateAddress('county', event.target.value)} className="mt-1 block w-full" />
                            </Field>
                            <Field id="address_postcode" label={fields.postcode} error={errors['registered_address.postcode']}>
                                <TextInput id="address_postcode" value={data.registered_address.postcode} onChange={(event) => updateAddress('postcode', event.target.value.toUpperCase())} className="mt-1 block w-full" required maxLength={12} />
                            </Field>
                            <Field id="address_country" label={fields.country} error={errors['registered_address.country']}>
                                <TextInput id="address_country" value={data.registered_address.country} className="mt-1 block w-full bg-gray-100 dark:bg-gray-900" readOnly />
                            </Field>
                        </div>
                    </Section>

                    <Section title={sections.accounting} description={sections.accounting_description}>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="functional_currency" label={fields.functional_currency} error={errors.functional_currency} hint={t.hints.gbp_only}>
                                <TextInput id="functional_currency" value={data.functional_currency} className="mt-1 block w-full bg-gray-100 dark:bg-gray-900" readOnly />
                            </Field>
                            <Field id="accounting_basis" label={fields.accounting_basis} error={errors.accounting_basis}>
                                <select id="accounting_basis" value={data.accounting_basis} onChange={(event) => setData('accounting_basis', event.target.value)} className={selectClass}>
                                    <option value="accrual">{options.accrual}</option>
                                    <option value="cash">{options.cash}</option>
                                </select>
                            </Field>
                            <Field id="financial_year_start" label={fields.financial_year_start} error={errors.financial_year_start}>
                                <TextInput id="financial_year_start" type="date" value={data.financial_year_start} onChange={(event) => setData('financial_year_start', event.target.value)} className="mt-1 block w-full" required />
                            </Field>
                            <Field id="financial_year_end" label={fields.financial_year_end} error={errors.financial_year_end}>
                                <TextInput id="financial_year_end" type="date" value={data.financial_year_end} onChange={(event) => setData('financial_year_end', event.target.value)} className="mt-1 block w-full" required />
                            </Field>
                        </div>
                    </Section>

                    <Section title={sections.vat} description={sections.vat_description}>
                        <label className="flex cursor-pointer items-center gap-3 text-sm font-medium text-gray-800 dark:text-gray-200">
                            <input type="checkbox" checked={data.vat_registered} onChange={(event) => setData('vat_registered', event.target.checked)} className="rounded border-gray-300 text-red-600 focus:ring-red-500" />
                            {fields.vat_registered}
                        </label>
                        <InputError message={errors.vat_registered} />
                        {data.vat_registered && (
                            <div className="grid gap-5 border-t border-gray-100 pt-5 sm:grid-cols-2 dark:border-gray-700">
                                <Field id="vat_number" label={fields.vat_number} error={errors.vat_number}>
                                    <TextInput id="vat_number" value={data.vat_number} onChange={(event) => setData('vat_number', event.target.value.toUpperCase())} className="mt-1 block w-full" required />
                                </Field>
                                <Field id="vat_scheme" label={fields.vat_scheme} error={errors.vat_scheme}>
                                    <select id="vat_scheme" value={data.vat_scheme} onChange={(event) => setData('vat_scheme', event.target.value)} className={selectClass} required>
                                        <option value="" disabled>—</option>
                                        <option value="standard">{options.standard}</option>
                                        <option value="cash">{options.cash_vat}</option>
                                        <option value="annual">{options.annual}</option>
                                        <option value="flat_rate">{options.flat_rate}</option>
                                    </select>
                                </Field>
                                <Field id="vat_effective_from" label={fields.vat_effective_from} error={errors.vat_effective_from}>
                                    <TextInput id="vat_effective_from" type="date" value={data.vat_effective_from} onChange={(event) => setData('vat_effective_from', event.target.value)} className="mt-1 block w-full" required />
                                </Field>
                                <Field id="vat_effective_to" label={fields.vat_effective_to} error={errors.vat_effective_to}>
                                    <TextInput id="vat_effective_to" type="date" value={data.vat_effective_to} onChange={(event) => setData('vat_effective_to', event.target.value)} className="mt-1 block w-full" />
                                </Field>
                            </div>
                        )}
                    </Section>

                    <Section title={sections.payroll} description={sections.payroll_description}>
                        <div className="grid gap-5 sm:grid-cols-2">
                            <Field id="paye_reference" label={fields.paye_reference} error={errors.paye_reference} hint={secretHint(profile.has_paye_reference)}>
                                <TextInput id="paye_reference" type="password" value={data.paye_reference} onChange={(event) => setData('paye_reference', event.target.value)} className="mt-1 block w-full" autoComplete="new-password" maxLength={32} />
                            </Field>
                            <Field id="accounts_office_reference" label={fields.accounts_office_reference} error={errors.accounts_office_reference} hint={secretHint(profile.has_accounts_office_reference)}>
                                <TextInput id="accounts_office_reference" type="password" value={data.accounts_office_reference} onChange={(event) => setData('accounts_office_reference', event.target.value)} className="mt-1 block w-full" autoComplete="new-password" maxLength={32} />
                            </Field>
                        </div>
                    </Section>

                    <div className="flex justify-end">
                        <PrimaryButton disabled={processing}>{processing ? t.saving : t.save}</PrimaryButton>
                    </div>
                </form>
            </div>
        </PortalLayout>
    );
}
