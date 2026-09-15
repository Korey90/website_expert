import { useState, type FormEvent } from 'react';
import axios from 'axios';
import { useGoogleReCaptcha } from 'react-google-recaptcha-v3';
import useRankCheckerTrans from '@/Hooks/useRankCheckerTrans';
import { pushEvent } from '@/utils/dataLayer';
import GoogleRankResult, { type RankCheckResult } from '@/Components/Tools/GoogleRankResult';

type Device = 'desktop' | 'mobile';

interface GoogleRankCheckerFormProps {
    locations: string[];
    defaultLocation: string;
    defaultDevice: Device;
    /** Where the result CTA links to. Defaults to /services/seo (ignored when onCtaAction is set). */
    ctaHref?: string;
    /** When set, the result CTA becomes a button calling this (with the checked domain) instead of navigating. */
    onCtaAction?: (domain: string) => void;
}

interface FormErrors {
    [field: string]: string[];
}

const OTHER_LOCATION = '__other__';

export default function GoogleRankCheckerForm({ locations, defaultLocation, defaultDevice, ctaHref, onCtaAction }: GoogleRankCheckerFormProps) {
    const t = useRankCheckerTrans();
    const { executeRecaptcha } = useGoogleReCaptcha();

    const [keyword, setKeyword] = useState('');
    const [website, setWebsite] = useState('');
    const [location, setLocation] = useState<string>(
        locations.includes(defaultLocation) ? defaultLocation : (locations[0] ?? OTHER_LOCATION)
    );
    const [customLocation, setCustomLocation] = useState('');
    const [device, setDevice] = useState<Device>(defaultDevice);
    const [status, setStatus] = useState<'idle' | 'submitting' | 'error' | 'rate_limited'>('idle');
    const [errorMessage, setErrorMessage] = useState<string | null>(null);
    const [errors, setErrors] = useState<FormErrors>({});
    const [result, setResult] = useState<RankCheckResult | null>(null);

    const isOtherLocation = location === OTHER_LOCATION;
    const effectiveLocation = isOtherLocation ? customLocation.trim() : location;

    async function handleSubmit(e: FormEvent) {
        e.preventDefault();
        if (status === 'submitting') return;

        setStatus('submitting');
        setErrors({});
        setErrorMessage(null);
        setResult(null);

        try {
            const recaptchaToken = executeRecaptcha ? await executeRecaptcha('google_rank_checker') : null;

            const response = await axios.post<RankCheckResult>(route('tools.google-rank-checker.check'), {
                keyword,
                website,
                location: effectiveLocation,
                device,
                recaptcha_token: recaptchaToken,
            });

            setResult(response.data);
            setStatus('idle');
            pushEvent('rank_check_completed', {
                status: response.data.status,
                position: response.data.position ?? undefined,
            });
        } catch (err) {
            const axiosError = err as { response?: { status: number; data?: { errors?: FormErrors; message?: string } } };
            if (axiosError.response?.status === 422 && axiosError.response.data?.errors) {
                setErrors(axiosError.response.data.errors);
                setStatus('idle');
                return;
            }
            if (axiosError.response?.status === 429) {
                setStatus('rate_limited');
                return;
            }
            setErrorMessage(axiosError.response?.data?.message ?? null);
            setStatus('error');
        }
    }

    return (
        <div className="rounded-2xl border border-neutral-200 dark:border-white/10 bg-white dark:bg-neutral-900 p-6 sm:p-8 shadow-sm">
            <form onSubmit={handleSubmit} className="space-y-5" noValidate>
                <div>
                    <label htmlFor="rc-keyword" className="block text-sm font-medium text-neutral-900 dark:text-white mb-1">
                        {t('form.keyword_label')}
                    </label>
                    <input
                        id="rc-keyword"
                        type="text"
                        value={keyword}
                        onChange={(e) => setKeyword(e.target.value)}
                        placeholder={t('form.keyword_placeholder')}
                        maxLength={150}
                        required
                        aria-invalid={Boolean(errors.keyword)}
                        className="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-950 px-4 py-2.5 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                    />
                    {errors.keyword && <p role="alert" className="mt-1 text-sm text-red-600">{errors.keyword[0]}</p>}
                </div>

                <div>
                    <label htmlFor="rc-website" className="block text-sm font-medium text-neutral-900 dark:text-white mb-1">
                        {t('form.website_label')}
                    </label>
                    <input
                        id="rc-website"
                        type="text"
                        value={website}
                        onChange={(e) => setWebsite(e.target.value)}
                        placeholder={t('form.website_placeholder')}
                        maxLength={255}
                        required
                        aria-invalid={Boolean(errors.website)}
                        className="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-950 px-4 py-2.5 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                    />
                    {errors.website && <p role="alert" className="mt-1 text-sm text-red-600">{errors.website[0]}</p>}
                </div>

                <div className="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <label htmlFor="rc-location" className="block text-sm font-medium text-neutral-900 dark:text-white mb-1">
                            {t('form.location_label')}
                        </label>
                        <select
                            id="rc-location"
                            value={location}
                            onChange={(e) => setLocation(e.target.value)}
                            className="w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-950 px-4 py-2.5 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                        >
                            {locations.map((loc) => (
                                <option key={loc} value={loc}>{loc}</option>
                            ))}
                            <option value={OTHER_LOCATION}>{t('form.location_custom_option')}</option>
                        </select>
                        {isOtherLocation && (
                            <input
                                type="text"
                                value={customLocation}
                                onChange={(e) => setCustomLocation(e.target.value)}
                                placeholder={t('form.location_custom_placeholder')}
                                maxLength={150}
                                required
                                className="mt-2 w-full rounded-lg border border-neutral-300 dark:border-white/10 bg-white dark:bg-neutral-950 px-4 py-2.5 text-neutral-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-brand-500"
                            />
                        )}
                        {errors.location && <p role="alert" className="mt-1 text-sm text-red-600">{errors.location[0]}</p>}
                    </div>

                    <div>
                        <span className="block text-sm font-medium text-neutral-900 dark:text-white mb-1">
                            {t('form.device_label')}
                        </span>
                        <div className="flex rounded-lg border border-neutral-300 dark:border-white/10 overflow-hidden">
                            {(['desktop', 'mobile'] as Device[]).map((d) => (
                                <button
                                    key={d}
                                    type="button"
                                    onClick={() => setDevice(d)}
                                    aria-pressed={device === d}
                                    className={`flex-1 px-4 py-2.5 text-sm font-medium transition ${
                                        device === d
                                            ? 'bg-brand-500 text-white'
                                            : 'bg-white dark:bg-neutral-950 text-neutral-700 dark:text-white/70'
                                    }`}
                                >
                                    {d === 'desktop' ? t('form.device_desktop') : t('form.device_mobile')}
                                </button>
                            ))}
                        </div>
                    </div>
                </div>

                <button
                    type="submit"
                    disabled={status === 'submitting' || (isOtherLocation && customLocation.trim() === '')}
                    className="w-full rounded-lg bg-brand-500 hover:bg-brand-600 disabled:opacity-60 text-white font-semibold px-6 py-3 transition"
                >
                    {status === 'submitting' ? t('form.loading_label') : t('form.submit_label')}
                </button>

                {status === 'error' && (
                    <p role="alert" className="text-sm text-red-600 text-center">{errorMessage ?? t('result.error_message')}</p>
                )}
                {status === 'rate_limited' && (
                    <p role="alert" className="text-sm text-amber-600 text-center">{t('errors.rate_limited')}</p>
                )}
            </form>

            {result && <GoogleRankResult result={result} ctaHref={ctaHref} onCtaAction={onCtaAction} />}
        </div>
    );
}
