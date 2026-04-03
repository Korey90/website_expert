/**
 * SourceAttribution — panel atrybucji: kanał, UTM, urządzenie, referrer.
 *
 * @param {{ source: object|null, lead: object }} props
 */

const DEVICE_ICONS = {
    mobile: (
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3" />
        </svg>
    ),
    tablet: (
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5h3m-6.75 2.25h10.5a2.25 2.25 0 002.25-2.25v-15a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 4.5v15a2.25 2.25 0 002.25 2.25z" />
        </svg>
    ),
    desktop: (
        <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
        </svg>
    ),
};

function Row({ label, value, mono = false, link = false }) {
    if (!value) return null;

    return (
        <div className="flex items-start justify-between py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <dt className="text-xs font-medium text-gray-500 dark:text-gray-400 w-32 shrink-0">{label}</dt>
            <dd className={`text-sm text-gray-900 dark:text-white flex-1 text-right truncate max-w-xs ${mono ? 'font-mono text-xs' : ''}`}>
                {link ? (
                    <a
                        href={value}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="text-brand-600 dark:text-brand-400 hover:underline"
                    >
                        {value.replace(/^https?:\/\//, '').substring(0, 60)}
                        {value.length > 60 ? '…' : ''}
                    </a>
                ) : value}
            </dd>
        </div>
    );
}

export default function SourceAttribution({ source, lead }) {
    if (!source) {
        return (
            <div className="rounded-xl bg-gray-50 dark:bg-gray-800/50 border border-gray-200 dark:border-gray-700 p-5">
                <p className="text-sm text-gray-500 dark:text-gray-400">
                    Source data not available for this lead.
                </p>
            </div>
        );
    }

    const channel = source.channel
        ?? (lead?.utm_medium === 'cpc' ? 'Paid Search'
        : lead?.utm_medium === 'email' ? 'Email'
        : lead?.utm_medium === 'social' ? 'Social'
        : lead?.utm_source === 'google' ? 'Organic Search'
        : 'Direct / Organic');

    const deviceIcon = DEVICE_ICONS[source.device_type] ?? DEVICE_ICONS.desktop;
    const deviceLabel = source.device_type
        ? source.device_type.charAt(0).toUpperCase() + source.device_type.slice(1)
        : 'Unknown';

    return (
        <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
            {/* Channel banner */}
            <div className="px-5 py-3 bg-gray-50 dark:bg-gray-700/40 border-b border-gray-200 dark:border-gray-700 flex items-center gap-2">
                <span className="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Channel</span>
                <span className="ml-1 text-sm font-semibold text-gray-900 dark:text-white">{channel}</span>
                {source.device_type && (
                    <span className="ml-auto flex items-center gap-1.5 text-xs text-gray-500 dark:text-gray-400">
                        {deviceIcon}
                        {deviceLabel}
                    </span>
                )}
            </div>

            <dl className="px-5 py-1">
                <Row label="Source Type" value={source.display_type ?? source.type} />
                {source.landing_page && (
                    <Row label="Landing Page" value={source.landing_page?.title} />
                )}
                <Row label="UTM Source"   value={source.utm_source   ?? lead?.utm_source} />
                <Row label="UTM Medium"   value={source.utm_medium   ?? lead?.utm_medium} />
                <Row label="UTM Campaign" value={source.utm_campaign ?? lead?.utm_campaign} />
                <Row label="UTM Content"  value={source.utm_content} />
                <Row label="UTM Term"     value={source.utm_term} />
                <Row label="Country"      value={source.country_code} />
                <Row label="Referrer"     value={source.referrer_url} link />
                <Row label="Page URL"     value={source.page_url} link />
            </dl>
        </div>
    );
}
