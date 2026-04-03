import { Link } from '@inertiajs/react';

function FieldRow({ label, value }) {
    return (
        <div className="flex items-start justify-between gap-4 border-b border-neutral-200/70 py-3 last:border-b-0 dark:border-neutral-800/80">
            <span className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">
                {label}
            </span>
            <span className="max-w-[60%] text-right text-sm text-neutral-700 dark:text-neutral-200">
                {value || '—'}
            </span>
        </div>
    );
}

export default function AiProfileSnapshot({ business, profile, completion, t }) {
    const primaryColor = profile?.brand_colors?.primary ?? '#111827';
    const services = Array.isArray(profile?.services) ? profile.services.slice(0, 4) : [];
    const missing = Array.isArray(completion?.missing) ? completion.missing : [];

    return (
        <section className="overflow-hidden rounded-[2rem] border border-neutral-200 bg-white shadow-sm dark:border-neutral-800 dark:bg-neutral-900">
            <div className="border-b border-neutral-200 bg-gradient-to-br from-neutral-950 via-neutral-900 to-brand-950 px-6 py-5 text-white dark:border-neutral-800">
                <div className="flex items-start justify-between gap-4">
                    <div>
                        <p className="text-xs font-semibold uppercase tracking-[0.25em] text-white/60">
                            {t('ai.ui.profile_title')}
                        </p>
                        <h3 className="mt-2 font-display text-2xl font-semibold">
                            {business?.name ?? 'Business'}
                        </h3>
                        <p className="mt-2 max-w-sm text-sm text-white/70">
                            {t('ai.ui.profile_description')}
                        </p>
                    </div>
                    <span
                        className="h-12 w-12 rounded-2xl border border-white/15 shadow-inner"
                        style={{ backgroundColor: primaryColor }}
                        aria-hidden="true"
                    />
                </div>

                <div className="mt-6 flex items-center justify-between gap-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                    <div>
                        <p className="text-xs uppercase tracking-[0.2em] text-white/50">
                            {t('ai.ui.completion')}
                        </p>
                        <p className="mt-1 text-sm font-semibold text-white">
                            {completion?.complete ? t('ai.ui.profile_complete') : t('ai.ui.profile_incomplete')}
                        </p>
                    </div>
                    <div className="text-right">
                        <div className="text-2xl font-semibold text-white">{completion?.percentage ?? 0}%</div>
                    </div>
                </div>
            </div>

            <div className="px-6 py-5">
                <FieldRow label="Tagline" value={profile?.tagline} />
                <FieldRow label={t('field_language')} value={(business?.locale ?? 'en').toUpperCase()} />
                <FieldRow label="Industry" value={profile?.industry} />
                <FieldRow label="Tone" value={profile?.tone_of_voice} />

                <div className="pt-4">
                    <p className="text-xs font-semibold uppercase tracking-[0.2em] text-neutral-400">
                        Services
                    </p>
                    <div className="mt-3 flex flex-wrap gap-2">
                        {services.length > 0 ? services.map((service) => (
                            <span
                                key={service}
                                className="rounded-full border border-brand-200 bg-brand-50 px-3 py-1 text-xs font-medium text-brand-700 dark:border-brand-900 dark:bg-brand-900/20 dark:text-brand-300"
                            >
                                {service}
                            </span>
                        )) : (
                            <span className="text-sm text-neutral-500 dark:text-neutral-400">—</span>
                        )}
                    </div>
                </div>

                {missing.length > 0 && (
                    <div className="mt-5 rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 dark:border-amber-900/60 dark:bg-amber-900/10">
                        <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                            {t('ai.ui.status_profile_missing')}
                        </p>
                        <p className="mt-2 text-xs leading-6 text-amber-700 dark:text-amber-300">
                            {missing.join(', ')}
                        </p>
                    </div>
                )}

                <Link
                    href={route('business.profile.edit')}
                    className="mt-5 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:text-brand-700 dark:text-brand-300 dark:hover:text-brand-200"
                >
                    {t('ai.ui.open_profile')}
                    <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        <path strokeLinecap="round" strokeLinejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </Link>
            </div>
        </section>
    );
}