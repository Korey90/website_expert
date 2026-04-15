import { useState } from 'react';
import { usePage } from '@inertiajs/react';
import { useConsentContext } from '@/Contexts/ConsentContext';

const CATEGORIES = [
    { key: 'analytics',   label: 'Analityczne',  labelEn: 'Analytics',    desc: 'Google Analytics (GA4)' },
    { key: 'marketing',   label: 'Marketingowe', labelEn: 'Marketing',    desc: 'Meta Pixel, Google Ads' },
    { key: 'preferences', label: 'Preferencje',  labelEn: 'Preferences',  desc: 'Interface settings' },
];

export default function CookieBanner() {
    const { locale = 'en', tracking } = usePage().props;
    const { bannerOpen, acceptAll, rejectAll, saveCustom } = useConsentContext();

    const [showDetails, setShowDetails] = useState(false);
    const [custom, setCustom] = useState({ analytics: false, marketing: false, preferences: false });

    if (!bannerOpen) return null;
    if (tracking?.cookie_consent_enabled === false) return null;

    const pl = locale === 'pl';

    return (
        <div className="fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-neutral-900 border-t border-neutral-200 dark:border-neutral-700 shadow-2xl">
            <div className="mx-auto max-w-5xl px-4 sm:px-6 py-5">
                <p className="text-sm text-neutral-700 dark:text-neutral-300 mb-4">
                    {pl
                        ? 'Używamy plików cookies, aby poprawić działanie serwisu i prowadzić analizę ruchu. Możesz zaakceptować wszystkie lub wybrać kategorie.'
                        : 'We use cookies to improve performance and analyse traffic. You can accept all or choose categories.'
                    }{' '}
                    <a href="/cookies" className="text-brand-400 hover:text-brand-600 tracking-wide font-semibold" target="_blank" rel="noopener noreferrer">
                        {pl ? 'Polityka cookies' : 'Cookie policy'}
                    </a>
                </p>

                {showDetails && (
                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-4">
                        {CATEGORIES.map(({ key, label, labelEn, desc }) => (
                            <label key={key} className="flex items-start gap-2 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    checked={custom[key]}
                                    onChange={e => setCustom(p => ({ ...p, [key]: e.target.checked }))}
                                    className="mt-1 accent-brand-500"
                                />
                                <span className="text-sm">
                                    <span className="font-medium text-neutral-800 dark:text-neutral-200">
                                        {pl ? label : labelEn}
                                    </span>
                                    <br />
                                    <span className="text-neutral-500 dark:text-neutral-400 text-xs">{desc}</span>
                                </span>
                            </label>
                        ))}
                    </div>
                )}

                <div className="flex flex-wrap items-center gap-2">
                    <button
                        onClick={acceptAll}
                        className="px-4 py-2 border border-brand-400 hover:bg-brand-400 text-brand-400 hover:text-neutral-100 text-sm font-medium rounded-md transition-colors tracking-wide"
                    >
                        {pl ? 'Akceptuj wszystkie' : 'Accept all'}
                    </button>

                    <button
                        onClick={rejectAll}
                        className="px-4 py-2 border border-neutral-300 dark:border-neutral-600 text-sm rounded-md text-neutral-700 dark:text-neutral-300 hover:bg-neutral-50 dark:hover:bg-neutral-800 transition-colors tracking-wide"
                    >
                        {pl ? 'Tylko niezbędne' : 'Necessary only'}
                    </button>

                    {showDetails ? (
                        <button
                            onClick={() => saveCustom(custom)}
                            className="px-4 py-2 border border-brand-400 hover:bg-brand-400 text-brand-400 hover:text-neutral-100 text-sm font-medium rounded-md transition-colors tracking-wide"
                        >
                            {pl ? 'Zapisz wybór' : 'Save selection'}
                        </button>
                    ) : (
                        <button
                            onClick={() => setShowDetails(true)}
                            className="px-2 py-2 text-sm text-neutral-500 dark:text-neutral-300 underline hover:text-neutral-700 dark:hover:text-neutral-200 transition-colors tracking-wide"
                        >
                            {pl ? 'Dostosuj' : 'Customise'}
                        </button>
                    )}
                </div>
            </div>
        </div>
    );
}
