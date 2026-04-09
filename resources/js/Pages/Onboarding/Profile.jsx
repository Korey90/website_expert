import { useForm, usePage, Head, Link } from '@inertiajs/react';
import ApplicationLogo from '@/Components/ApplicationLogo';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import ColorPicker from '@/Components/Business/ColorPicker';
import ServicesList from '@/Components/Business/ServicesList';
import ToneSelector from '@/Components/Business/ToneSelector';

/**
 * Onboarding/Profile — step 1 of 2
 * Collects brand data: tagline, industry, tone_of_voice, services, primary color, website.
 */
export default function OnboardingProfile({ business, profile, step, totalSteps, industries, tonesOfVoice }) {
    const { flash } = usePage().props;

    const { data, setData, post, processing, errors } = useForm({
        tagline:        profile?.tagline       ?? '',
        industry:       profile?.industry      ?? '',
        tone_of_voice:  profile?.tone_of_voice ?? 'professional',
        services:       profile?.services      ?? [],
        website_url:    profile?.website_url   ?? '',
        brand_colors: {
            primary:   profile?.brand_colors?.primary   ?? '#ff2b17',
            secondary: profile?.brand_colors?.secondary ?? '#000000',
        },
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('onboarding.profile.save'));
    };

    const progressPct = Math.round((step / totalSteps) * 100);

    return (
        <div className="min-h-screen bg-gray-50 dark:bg-gray-950 flex flex-col">
            <Head title="Set up your profile" />

            {/* Top bar */}
            <header className="bg-white dark:bg-gray-900 border-b border-gray-200 dark:border-gray-800 px-4 py-4">
                <div className="mx-auto max-w-2xl flex items-center justify-between">
                    <Link href="/">
                        <ApplicationLogo className="h-8 w-auto fill-current text-gray-900 dark:text-white" />
                    </Link>
                    <span className="text-sm text-gray-500 dark:text-gray-400 font-medium">
                        Step {step} of {totalSteps}
                    </span>
                </div>

                {/* Progress bar */}
                <div className="mx-auto max-w-2xl mt-3">
                    <div className="h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                        <div
                            className="h-full rounded-full bg-brand-500 transition-all duration-500"
                            style={{ width: `${progressPct}%` }}
                        />
                    </div>
                </div>
            </header>

            {/* Main content */}
            <main className="flex-1 py-10 px-4">
                <div className="mx-auto max-w-2xl">
                    {/* Intro */}
                    <div className="mb-8">
                        <h1 className="text-2xl font-display font-bold text-gray-900 dark:text-white">
                            Tell us about <span className="text-brand-500">{business?.name}</span>
                        </h1>
                        <p className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                            This helps us generate better landing pages and AI content for your brand.
                            You can always change these later.
                        </p>
                    </div>

                    {flash?.warning && (
                        <div className="mb-6 rounded-lg bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 px-4 py-3 text-sm text-amber-800 dark:text-amber-300">
                            {flash.warning}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">
                        {/* Card: Brand identity */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 space-y-5">
                            <h2 className="text-base font-semibold text-gray-900 dark:text-white">
                                Brand identity
                            </h2>

                            {/* Tagline */}
                            <div>
                                <InputLabel htmlFor="tagline" value="Tagline" />
                                <p className="text-xs text-gray-500 dark:text-gray-400 mb-1">
                                    A short phrase that captures your business. <span className="text-gray-400">(optional)</span>
                                </p>
                                <TextInput
                                    id="tagline"
                                    value={data.tagline}
                                    onChange={(e) => setData('tagline', e.target.value)}
                                    className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="Your success online"
                                    maxLength={255}
                                />
                                <InputError message={errors.tagline} className="mt-1" />
                            </div>

                            {/* Industry */}
                            <div>
                                <InputLabel htmlFor="industry" value="Industry" />
                                <select
                                    id="industry"
                                    value={data.industry}
                                    onChange={(e) => setData('industry', e.target.value)}
                                    className={
                                        'mt-1 block w-full rounded-md border shadow-sm text-sm py-2 px-3 ' +
                                        'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 ' +
                                        'border-gray-300 dark:border-gray-600 ' +
                                        'focus:border-brand-500 focus:ring-brand-500'
                                    }
                                >
                                    <option value="">Select your industry…</option>
                                    {Object.entries(industries ?? {}).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                                <InputError message={errors.industry} className="mt-1" />
                            </div>

                            {/* Primary brand colour */}
                            <ColorPicker
                                label="Primary brand colour"
                                value={data.brand_colors.primary}
                                onChange={(hex) => setData('brand_colors', { ...data.brand_colors, primary: hex })}
                                error={errors['brand_colors.primary']}
                            />
                        </section>

                        {/* Card: Tone of voice */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">
                                Tone of voice
                            </h2>
                            <ToneSelector
                                value={data.tone_of_voice}
                                onChange={(tone) => setData('tone_of_voice', tone)}
                                tonesOfVoice={tonesOfVoice ?? {}}
                                error={errors.tone_of_voice}
                            />
                        </section>

                        {/* Card: Services */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-1">
                                Services you offer
                            </h2>
                            <p className="text-xs text-gray-500 dark:text-gray-400 mb-4">
                                These will appear in AI-generated landing pages. <span className="text-gray-400">(optional)</span>
                            </p>
                            <ServicesList
                                value={data.services}
                                onChange={(list) => setData('services', list)}
                                placeholder="e.g. Web Design, SEO, Social Media…"
                                error={errors.services}
                            />
                        </section>

                        {/* Card: Website */}
                        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6">
                            <h2 className="text-base font-semibold text-gray-900 dark:text-white mb-4">
                                Website <span className="text-sm font-normal text-gray-400">(optional)</span>
                            </h2>
                            <TextInput
                                id="website_url"
                                type="url"
                                value={data.website_url}
                                onChange={(e) => setData('website_url', e.target.value)}
                                className="block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="https://yourwebsite.com"
                            />
                            <InputError message={errors.website_url} className="mt-1" />
                        </section>

                        {/* Actions */}
                        <div className="flex items-center justify-between pt-2">
                            <Link
                                href={route('portal.dashboard')}
                                className="text-sm text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 underline-offset-2 hover:underline"
                            >
                                Skip for now
                            </Link>

                            <PrimaryButton disabled={processing} className="px-8">
                                {processing ? 'Saving…' : 'Save & Continue →'}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </main>
        </div>
    );
}
