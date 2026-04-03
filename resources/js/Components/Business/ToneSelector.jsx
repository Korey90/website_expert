/**
 * ToneSelector — radio cards for selecting brand tone of voice.
 * tonesOfVoice: Record<string, string>  e.g. { professional: 'Professional & Formal', ... }
 */

const TONE_ICONS = {
    professional: (
        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M20.25 14.15v4.073a2.25 2.25 0 0 1-2.25 2.25h-12a2.25 2.25 0 0 1-2.25-2.25V7.5a2.25 2.25 0 0 1 2.25-2.25H12m8.25 0V3.75M20.25 3.75H16.5M20.25 3.75 15 9" />
        </svg>
    ),
    friendly: (
        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75s.168-.75.375-.75.375.336.375.75Zm4.875 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Z" />
        </svg>
    ),
    bold: (
        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 13.5l10.5-11.25L12 10.5h8.25L9.75 21.75 12 13.5H3.75z" />
        </svg>
    ),
    minimalist: (
        <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
            <path strokeLinecap="round" strokeLinejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12" />
        </svg>
    ),
};

export default function ToneSelector({ label, value, onChange, tonesOfVoice = {}, error }) {
    return (
        <div>
            {label && (
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    {label}
                </label>
            )}

            <div className="grid grid-cols-2 gap-3 sm:grid-cols-4">
                {Object.entries(tonesOfVoice).map(([key, displayName]) => {
                    const isSelected = value === key;
                    return (
                        <button
                            key={key}
                            type="button"
                            onClick={() => onChange(key)}
                            className={
                                'relative flex flex-col items-center gap-2 rounded-xl border-2 px-3 py-4 text-center ' +
                                'transition-all duration-150 focus:outline-none focus:ring-2 focus:ring-brand-500 ' +
                                (isSelected
                                    ? 'border-brand-500 bg-brand-50 dark:bg-brand-900/20 text-brand-700 dark:text-brand-300'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-600 dark:text-gray-400 hover:border-gray-300 dark:hover:border-gray-500')
                            }
                            aria-pressed={isSelected}
                        >
                            <span className={isSelected ? 'text-brand-600 dark:text-brand-400' : 'text-gray-400 dark:text-gray-500'}>
                                {TONE_ICONS[key] ?? null}
                            </span>
                            <span className="text-xs font-medium leading-tight">{displayName}</span>

                            {isSelected && (
                                <span className="absolute top-2 right-2 h-2 w-2 rounded-full bg-brand-500" />
                            )}
                        </button>
                    );
                })}
            </div>

            {error && (
                <p className="mt-1 text-sm text-red-600 dark:text-red-400">{error}</p>
            )}
        </div>
    );
}
