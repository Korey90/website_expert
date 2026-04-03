/**
 * ProfileCompletionBar — shows how complete the business profile is.
 * percentage: 0–100
 * missing: string[] — list of missing field names
 */
export default function ProfileCompletionBar({ percentage = 0, missing = [], compact = false }) {
    const color =
        percentage >= 100 ? 'bg-green-500' :
        percentage >= 60  ? 'bg-brand-500' :
        percentage >= 30  ? 'bg-amber-500' :
                            'bg-red-500';

    if (compact) {
        return (
            <div className="flex items-center gap-2">
                <div className="flex-1 h-1.5 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden">
                    <div
                        className={`h-full rounded-full transition-all duration-500 ${color}`}
                        style={{ width: `${Math.min(percentage, 100)}%` }}
                    />
                </div>
                <span className="text-xs font-medium text-gray-500 dark:text-gray-400 whitespace-nowrap">
                    {percentage}%
                </span>
            </div>
        );
    }

    return (
        <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-4">
            <div className="flex items-center justify-between mb-2">
                <span className="text-sm font-semibold text-gray-800 dark:text-gray-200">
                    Profile completeness
                </span>
                <span className={`text-sm font-bold ${percentage >= 100 ? 'text-green-600 dark:text-green-400' : 'text-gray-600 dark:text-gray-400'}`}>
                    {percentage}%
                </span>
            </div>

            <div className="h-2 rounded-full bg-gray-200 dark:bg-gray-700 overflow-hidden mb-3">
                <div
                    className={`h-full rounded-full transition-all duration-700 ${color}`}
                    style={{ width: `${Math.min(percentage, 100)}%` }}
                />
            </div>

            {percentage >= 100 ? (
                <p className="text-xs text-green-600 dark:text-green-400 font-medium flex items-center gap-1">
                    <svg className="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
                        <path fillRule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clipRule="evenodd" />
                    </svg>
                    Profile complete — ready for AI landing page generation
                </p>
            ) : missing.length > 0 ? (
                <p className="text-xs text-gray-500 dark:text-gray-400">
                    Missing: <span className="font-medium text-gray-700 dark:text-gray-300">{missing.join(', ')}</span>
                </p>
            ) : null}
        </div>
    );
}
