import { useForm, usePage, Head, Link } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import LogoUploader from '@/Components/Business/LogoUploader';
import ColorPicker from '@/Components/Business/ColorPicker';

const LOCALES = [
    { value: 'en', label: 'English' },
    { value: 'pl', label: 'Polski' },
    { value: 'pt', label: 'Português' },
];

const COMMON_TIMEZONES = [
    'UTC',
    'Europe/London',
    'Europe/Warsaw',
    'Europe/Lisbon',
    'Europe/Berlin',
    'Europe/Paris',
    'America/New_York',
    'America/Chicago',
    'America/Los_Angeles',
    'Asia/Tokyo',
    'Australia/Sydney',
];

/**
 * Business/Settings — name, locale, timezone, logo, primary colour.
 * Props: business: { id, name, locale, timezone, logo_url, primary_color, plan }
 */
export default function BusinessSettings({ business, client }) {
    const { flash } = usePage().props;

    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        name:          business?.name          ?? '',
        locale:        business?.locale        ?? 'en',
        timezone:      business?.timezone      ?? 'Europe/London',
        primary_color: business?.primary_color ?? '#ff2b17',
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('business.update'));
    };

    return (
        <PortalLayout client={client}>
            <Head title="Business Settings" />

            <div className="max-w-2xl mx-auto space-y-6">

                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">Business Settings</h1>
                    <Link
                        href={route('business.profile.edit')}
                        className="text-sm text-red-600 hover:underline"
                    >
                        Edit brand profile →
                    </Link>
                </div>

                    {/* Flash success */}
                    {(flash?.success || recentlySuccessful) && (
                        <div className="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                            <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {flash?.success ?? 'Settings saved.'}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        {/* Card: General */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 space-y-5">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-white">General</h3>

                            {/* Business name */}
                            <div>
                                <InputLabel htmlFor="name" value="Business name" />
                                <TextInput
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    required
                                    maxLength={255}
                                />
                                <InputError message={errors.name} className="mt-1" />
                            </div>

                            {/* Locale */}
                            <div>
                                <InputLabel htmlFor="locale" value="Default language" />
                                <select
                                    id="locale"
                                    value={data.locale}
                                    onChange={(e) => setData('locale', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm py-2 px-3 focus:border-brand-500 focus:ring-brand-500"
                                >
                                    {LOCALES.map(({ value, label }) => (
                                        <option key={value} value={value}>{label}</option>
                                    ))}
                                </select>
                                <InputError message={errors.locale} className="mt-1" />
                            </div>

                            {/* Timezone */}
                            <div>
                                <InputLabel htmlFor="timezone" value="Timezone" />
                                <select
                                    id="timezone"
                                    value={data.timezone}
                                    onChange={(e) => setData('timezone', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm py-2 px-3 focus:border-brand-500 focus:ring-brand-500"
                                >
                                    {COMMON_TIMEZONES.map((tz) => (
                                        <option key={tz} value={tz}>{tz}</option>
                                    ))}
                                </select>
                                <InputError message={errors.timezone} className="mt-1" />
                            </div>

                            {/* Primary colour */}
                            <ColorPicker
                                label="Primary brand colour"
                                value={data.primary_color}
                                onChange={(hex) => setData('primary_color', hex)}
                                error={errors.primary_color}
                            />
                        </section>

                        {/* Card: Logo */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-4">Logo</h3>
                            <LogoUploader
                                currentLogoUrl={business?.logo_url ?? null}
                                onUploaded={() => { /* logo saved server-side; no form field needed */ }}
                                onDeleted={() => { /* logo deleted server-side */ }}
                            />
                            <p className="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                Logo is uploaded and saved immediately — no need to submit the form.
                            </p>
                        </section>

                        {/* Plan badge */}
                        {business?.plan && (
                            <div className="flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 px-1">
                                <span>Current plan:</span>
                                <span className="inline-block rounded-full bg-gray-100 dark:bg-gray-700 px-2 py-0.5 font-semibold uppercase tracking-wide text-gray-600 dark:text-gray-300">
                                    {business.plan}
                                </span>
                            </div>
                        )}

                        {/* Actions */}
                        <div className="flex justify-end">
                            <PrimaryButton disabled={processing}>
                                {processing ? 'Saving…' : 'Save settings'}
                            </PrimaryButton>
                        </div>
                    </form>
            </div>
        </PortalLayout>
    );
}
