/**
 * ConsentBadge — wyświetla status zgody GDPR.
 * Gdy showDetails=true, renderuje pełny panel z treścią zgody.
 *
 * @param {{ consent: object|null, showDetails?: boolean }} props
 */
export default function ConsentBadge({ consent, showDetails = false }) {
    if (!consent) {
        return (
            <span className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-400">
                <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M20 12H4" />
                </svg>
                No Consent Data
            </span>
        );
    }

    if (!showDetails) {
        return consent.given ? (
            <span className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300">
                <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                </svg>
                Consent Given
            </span>
        ) : (
            <span className="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300">
                <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
                No Consent
            </span>
        );
    }

    // showDetails = true — pełny panel
    const ipFingerprint = consent.ip_hash
        ? `...${consent.ip_hash.slice(-8)}`
        : null;

    const collectedDate = consent.collected_at
        ? new Date(consent.collected_at).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
          })
        : '—';

    return (
        <div className="space-y-4">
            <div className="flex items-center gap-3">
                <ConsentBadge consent={consent} showDetails={false} />
                {consent.consent_version && (
                    <span className="text-xs text-gray-500 dark:text-gray-400">
                        v{consent.consent_version}
                    </span>
                )}
            </div>

            {consent.consent_text && consent.consent_text !== '[DELETED]' && (
                <div className="rounded-lg bg-gray-50 dark:bg-gray-700/40 border border-gray-200 dark:border-gray-600 p-4">
                    <p className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Consent Text</p>
                    <p className="text-sm text-gray-700 dark:text-gray-300 leading-relaxed">
                        {consent.consent_text}
                    </p>
                </div>
            )}

            <dl className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Collected</dt>
                    <dd className="mt-0.5 text-sm text-gray-900 dark:text-white">{collectedDate}</dd>
                </div>
                <div>
                    <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Locale</dt>
                    <dd className="mt-0.5 text-sm text-gray-900 dark:text-white uppercase">{consent.locale ?? '—'}</dd>
                </div>
                {consent.source_url && (
                    <div className="sm:col-span-2">
                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">Source URL</dt>
                        <dd className="mt-0.5 text-sm text-gray-700 dark:text-gray-300 truncate">{consent.source_url}</dd>
                    </div>
                )}
                {ipFingerprint && (
                    <div>
                        <dt className="text-xs font-medium text-gray-500 dark:text-gray-400">IP Fingerprint</dt>
                        <dd className="mt-0.5 font-mono text-xs text-gray-600 dark:text-gray-400">{ipFingerprint}</dd>
                    </div>
                )}
            </dl>
        </div>
    );
}
