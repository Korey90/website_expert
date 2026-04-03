import { router } from '@inertiajs/react';
import { useState } from 'react';

/**
 * ApiTokenCard — karta API tokena z opcją revoke.
 *
 * @param {{ token: object, onRevoked?: () => void }} props
 */
export default function ApiTokenCard({ token }) {
    const [confirming, setConfirming] = useState(false);
    const [revoking, setRevoking] = useState(false);

    const handleRevoke = () => {
        setRevoking(true);
        router.delete(route('business.api-tokens.destroy', token.id), {
            preserveScroll: true,
            onFinish: () => {
                setRevoking(false);
                setConfirming(false);
            },
        });
    };

    const formatDate = (dateStr) => {
        if (!dateStr) return null;
        return new Date(dateStr).toLocaleDateString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
        });
    };

    const formatDateTime = (dateStr) => {
        if (!dateStr) return null;
        return new Date(dateStr).toLocaleString('en-GB', {
            day: '2-digit', month: 'short', year: 'numeric',
            hour: '2-digit', minute: '2-digit',
        });
    };

    return (
        <div className={`rounded-xl border ${token.is_active ? 'border-gray-200 dark:border-gray-700' : 'border-gray-100 dark:border-gray-800 opacity-60'} bg-white dark:bg-gray-800 p-5`}>
            <div className="flex items-start justify-between gap-4">
                <div className="min-w-0 flex-1">
                    <div className="flex items-center gap-2 flex-wrap">
                        <h3 className="text-sm font-semibold text-gray-900 dark:text-white truncate">
                            {token.name}
                        </h3>
                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${
                            token.is_active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300'
                                : 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400'
                        }`}>
                            {token.is_active ? 'Active' : 'Revoked'}
                        </span>
                    </div>

                    <dl className="mt-2 flex flex-wrap gap-x-5 gap-y-1">
                        <div className="flex items-center gap-1">
                            <dt className="text-xs text-gray-400 dark:text-gray-500">Created</dt>
                            <dd className="text-xs text-gray-600 dark:text-gray-400">{formatDate(token.created_at)}</dd>
                        </div>
                        <div className="flex items-center gap-1">
                            <dt className="text-xs text-gray-400 dark:text-gray-500">Last used</dt>
                            <dd className="text-xs text-gray-600 dark:text-gray-400">
                                {token.last_used_at ? formatDateTime(token.last_used_at) : 'Never'}
                            </dd>
                        </div>
                        {token.expires_at && (
                            <div className="flex items-center gap-1">
                                <dt className="text-xs text-gray-400 dark:text-gray-500">Expires</dt>
                                <dd className="text-xs text-gray-600 dark:text-gray-400">{formatDate(token.expires_at)}</dd>
                            </div>
                        )}
                    </dl>
                </div>

                {token.is_active && (
                    <div className="shrink-0">
                        {confirming ? (
                            <div className="flex items-center gap-2">
                                <span className="text-xs text-gray-500 dark:text-gray-400">Sure?</span>
                                <button
                                    type="button"
                                    onClick={handleRevoke}
                                    disabled={revoking}
                                    className="rounded-lg bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-xs font-medium px-3 py-1.5 transition"
                                >
                                    {revoking ? 'Revoking…' : 'Yes, revoke'}
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setConfirming(false)}
                                    className="rounded-lg border border-gray-300 dark:border-gray-600 text-gray-600 dark:text-gray-400 text-xs font-medium px-3 py-1.5 hover:bg-gray-50 dark:hover:bg-gray-700 transition"
                                >
                                    Cancel
                                </button>
                            </div>
                        ) : (
                            <button
                                type="button"
                                onClick={() => setConfirming(true)}
                                className="rounded-lg border border-red-200 dark:border-red-800 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-xs font-medium px-3 py-1.5 transition"
                            >
                                Revoke
                            </button>
                        )}
                    </div>
                )}
            </div>

            {confirming && (
                <p className="mt-3 text-xs text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded-lg px-3 py-2">
                    This will immediately stop all integrations using this token. This cannot be undone.
                </p>
            )}
        </div>
    );
}
