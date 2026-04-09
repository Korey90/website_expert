import { useForm, usePage, Head, Link } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import ColorPicker from '@/Components/Business/ColorPicker';
import ServicesList from '@/Components/Business/ServicesList';
import ToneSelector from '@/Components/Business/ToneSelector';
import ProfileCompletionBar from '@/Components/Business/ProfileCompletionBar';

/**
 * Business/Profile — full brand profile editor.
 * Props:
 *   profile        — BusinessProfile model data
 *   business       — { id, name, logo_url }
 *   isComplete     — bool
 *   industries     — Record<string, string>
 *   tonesOfVoice   — Record<string, string>
 *   completion     — { complete: bool, percentage: number, missing: string[] }
 */
export default function BusinessProfile({ profile, business, isComplete, industries, tonesOfVoice, completion, client }) {
    const { flash } = usePage().props;

    const { data, setData, patch, processing, errors, recentlySuccessful } = useForm({
        tagline:         profile?.tagline         ?? '',
        description:     profile?.description     ?? '',
        industry:        profile?.industry        ?? '',
        tone_of_voice:   profile?.tone_of_voice   ?? 'professional',
        services:        profile?.services        ?? [],
        website_url:     profile?.website_url     ?? '',
        brand_colors: {
            primary:   profile?.brand_colors?.primary   ?? '#ff2b17',
            secondary: profile?.brand_colors?.secondary ?? '#000000',
            accent:    profile?.brand_colors?.accent    ?? '#ffffff',
        },
        fonts: {
            heading: profile?.fonts?.heading ?? 'Syne',
            body:    profile?.fonts?.body    ?? 'Inter',
        },
        social_links: {
            facebook:  profile?.social_links?.facebook  ?? '',
            instagram: profile?.social_links?.instagram ?? '',
            linkedin:  profile?.social_links?.linkedin  ?? '',
            twitter:   profile?.social_links?.twitter   ?? '',
        },
        seo_keywords:    profile?.seo_keywords   ?? [],
    });

    const submit = (e) => {
        e.preventDefault();
        patch(route('business.profile.update'));
    };

    const SectionCard = ({ title, subtitle, children }) => (
        <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-6 space-y-5">
            <div>
                <h3 className="text-base font-semibold text-gray-900 dark:text-white">{title}</h3>
                {subtitle && <p className="mt-0.5 text-sm text-gray-500 dark:text-gray-400">{subtitle}</p>}
            </div>
            {children}
        </section>
    );

    return (
        <PortalLayout client={client}>
            <Head title="Brand Profile" />

            <div className="max-w-3xl mx-auto space-y-6">

                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">
                        Brand Profile
                        {business?.name && (
                            <span className="ml-2 text-lg font-normal text-gray-400">
                                — {business.name}
                            </span>
                        )}
                    </h1>
                    <Link
                        href={route('business.edit')}
                        className="text-sm text-red-600 hover:underline"
                    >
                        ← Business settings
                    </Link>
                </div>

                    {/* Completion bar */}
                    {completion && (
                        <ProfileCompletionBar
                            percentage={completion.percentage ?? 0}
                            missing={completion.missing ?? []}
                        />
                    )}

                    {/* Flash success */}
                    {(flash?.success || recentlySuccessful) && (
                        <div className="rounded-lg bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 px-4 py-3 text-sm text-green-700 dark:text-green-300 flex items-center gap-2">
                            <svg className="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                            </svg>
                            {flash?.success ?? 'Profile saved.'}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-6">

                        {/* Brand identity */}
                        <SectionCard
                            title="Brand identity"
                            subtitle="Describe your company to help AI generate accurate content."
                        >
                            {/* Tagline */}
                            <div>
                                <InputLabel htmlFor="tagline" value="Tagline" />
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

                            {/* Description */}
                            <div>
                                <InputLabel htmlFor="description" value="Description" />
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                    rows={4}
                                    maxLength={2000}
                                    placeholder="A few sentences about what you do, who you serve and what makes you different."
                                    className={
                                        'mt-1 block w-full rounded-md border text-sm shadow-sm px-3 py-2 ' +
                                        'bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 ' +
                                        'border-gray-300 dark:border-gray-600 ' +
                                        'focus:border-brand-500 focus:ring-brand-500 placeholder-gray-400'
                                    }
                                />
                                <div className="flex justify-between mt-1">
                                    <InputError message={errors.description} />
                                    <span className="text-xs text-gray-400">{data.description.length}/2000</span>
                                </div>
                            </div>

                            {/* Industry */}
                            <div>
                                <InputLabel htmlFor="industry" value="Industry" />
                                <select
                                    id="industry"
                                    value={data.industry}
                                    onChange={(e) => setData('industry', e.target.value)}
                                    className="mt-1 block w-full rounded-md border border-gray-300 dark:border-gray-600 shadow-sm bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 text-sm py-2 px-3 focus:border-brand-500 focus:ring-brand-500"
                                >
                                    <option value="">Select industry…</option>
                                    {Object.entries(industries ?? {}).map(([key, label]) => (
                                        <option key={key} value={key}>{label}</option>
                                    ))}
                                </select>
                                <InputError message={errors.industry} className="mt-1" />
                            </div>

                            {/* Website */}
                            <div>
                                <InputLabel htmlFor="website_url" value="Website URL" />
                                <TextInput
                                    id="website_url"
                                    type="url"
                                    value={data.website_url}
                                    onChange={(e) => setData('website_url', e.target.value)}
                                    className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="https://yourwebsite.com"
                                />
                                <InputError message={errors.website_url} className="mt-1" />
                            </div>
                        </SectionCard>

                        {/* Tone of voice */}
                        <SectionCard
                            title="Tone of voice"
                            subtitle="How should AI write copy for your brand?"
                        >
                            <ToneSelector
                                value={data.tone_of_voice}
                                onChange={(tone) => setData('tone_of_voice', tone)}
                                tonesOfVoice={tonesOfVoice ?? {}}
                                error={errors.tone_of_voice}
                            />
                        </SectionCard>

                        {/* Services */}
                        <SectionCard
                            title="Services"
                            subtitle="List the services you offer — they'll be mentioned in your landing pages."
                        >
                            <ServicesList
                                value={data.services}
                                onChange={(list) => setData('services', list)}
                                placeholder="e.g. Web Design, SEO…"
                                error={errors.services}
                            />
                        </SectionCard>

                        {/* Brand colours */}
                        <SectionCard
                            title="Brand colours"
                            subtitle="Used in AI-generated landing pages to match your visual identity."
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <ColorPicker
                                    label="Primary"
                                    value={data.brand_colors.primary}
                                    onChange={(hex) => setData('brand_colors', { ...data.brand_colors, primary: hex })}
                                    error={errors['brand_colors.primary']}
                                />
                                <ColorPicker
                                    label="Secondary"
                                    value={data.brand_colors.secondary}
                                    onChange={(hex) => setData('brand_colors', { ...data.brand_colors, secondary: hex })}
                                    error={errors['brand_colors.secondary']}
                                />
                                <ColorPicker
                                    label="Accent"
                                    value={data.brand_colors.accent}
                                    onChange={(hex) => setData('brand_colors', { ...data.brand_colors, accent: hex })}
                                    error={errors['brand_colors.accent']}
                                />
                            </div>
                        </SectionCard>

                        {/* Typography */}
                        <SectionCard
                            title="Typography"
                            subtitle="Font names used in AI-generated pages. Must match Google Fonts names."
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div>
                                    <InputLabel htmlFor="font_heading" value="Heading font" />
                                    <TextInput
                                        id="font_heading"
                                        value={data.fonts.heading}
                                        onChange={(e) => setData('fonts', { ...data.fonts, heading: e.target.value })}
                                        className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Syne"
                                    />
                                    <InputError message={errors['fonts.heading']} className="mt-1" />
                                </div>
                                <div>
                                    <InputLabel htmlFor="font_body" value="Body font" />
                                    <TextInput
                                        id="font_body"
                                        value={data.fonts.body}
                                        onChange={(e) => setData('fonts', { ...data.fonts, body: e.target.value })}
                                        className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                        placeholder="Inter"
                                    />
                                    <InputError message={errors['fonts.body']} className="mt-1" />
                                </div>
                            </div>
                        </SectionCard>

                        {/* Social links */}
                        <SectionCard
                            title="Social media"
                            subtitle="Links used in generated landing pages. All optional."
                        >
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                {(['facebook', 'instagram', 'linkedin', 'twitter']).map((platform) => (
                                    <div key={platform}>
                                        <InputLabel
                                            htmlFor={`social_${platform}`}
                                            value={platform.charAt(0).toUpperCase() + platform.slice(1)}
                                        />
                                        <TextInput
                                            id={`social_${platform}`}
                                            type="url"
                                            value={data.social_links[platform]}
                                            onChange={(e) => setData('social_links', { ...data.social_links, [platform]: e.target.value })}
                                            className="mt-1 block w-full dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                            placeholder={`https://${platform}.com/yourpage`}
                                        />
                                        <InputError message={errors[`social_links.${platform}`]} className="mt-1" />
                                    </div>
                                ))}
                            </div>
                        </SectionCard>

                        {/* SEO keywords */}
                        <SectionCard
                            title="SEO keywords"
                            subtitle="Used for meta tags in AI-generated landing pages."
                        >
                            <ServicesList
                                value={data.seo_keywords}
                                onChange={(list) => setData('seo_keywords', list)}
                                placeholder="e.g. marketing agency, SEO Kraków…"
                                max={20}
                                error={errors.seo_keywords}
                            />
                        </SectionCard>

                        {/* Save */}
                        <div className="flex items-center justify-end gap-4 pt-2">
                            {recentlySuccessful && (
                                <span className="text-sm text-green-600 dark:text-green-400">Saved!</span>
                            )}
                            <PrimaryButton disabled={processing} className="px-8">
                                {processing ? 'Saving…' : 'Save profile'}
                            </PrimaryButton>
                        </div>
                    </form>
            </div>
        </PortalLayout>
    );
}
