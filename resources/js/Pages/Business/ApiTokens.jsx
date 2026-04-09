import { Head, usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import PortalLayout from '@/Layouts/PortalLayout';
import ApiTokenCard from '@/Components/Lead/ApiTokenCard';
import InputLabel from '@/Components/InputLabel';
import InputError from '@/Components/InputError';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import { useApiTokens } from '@/Hooks/useApiTokens';

function NewTokenAlert({ token, onCopy, copied }) {
    const ref = useRef(null);

    useEffect(() => {
        if (ref.current) {
            ref.current.select();
        }
    }, []);

    return (
        <div className="rounded-xl border-2 border-amber-400 dark:border-amber-500 bg-amber-50 dark:bg-amber-900/20 p-5 space-y-3">
            <div className="flex items-start gap-3">
                <svg className="h-5 w-5 text-amber-500 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                    <path strokeLinecap="round" strokeLinejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                </svg>
                <div>
                    <p className="text-sm font-semibold text-amber-800 dark:text-amber-200">
                        Copy your token now — it will never be shown again.
                    </p>
                    <p className="text-xs text-amber-600 dark:text-amber-400 mt-0.5">
                        Store it securely. If you lose it, you'll need to create a new token.
                    </p>
                </div>
            </div>

            <div className="flex items-center gap-2">
                <input
                    ref={ref}
                    type="text"
                    readOnly
                    value={token}
                    className="flex-1 rounded-xl border border-amber-300 dark:border-amber-600 bg-white dark:bg-gray-900 font-mono text-xs px-4 py-2.5 text-gray-900 dark:text-white focus:ring-2 focus:ring-amber-400 cursor-text"
                    onClick={(e) => e.target.select()}
                />
                <button
                    type="button"
                    onClick={() => onCopy(token)}
                    className={`flex items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold transition whitespace-nowrap ${
                        copied
                            ? 'bg-green-600 text-white'
                            : 'bg-amber-500 hover:bg-amber-600 text-white'
                    }`}
                >
                    {copied ? (
                        <>
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
                            </svg>
                            Copied!
                        </>
                    ) : (
                        <>
                            <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M15.666 3.888A2.25 2.25 0 0013.5 2.25h-3c-1.03 0-1.9.693-2.166 1.638m7.332 0c.055.194.084.4.084.612v0a.75.75 0 01-.75.75H9a.75.75 0 01-.75-.75v0c0-.212.03-.418.084-.612m7.332 0c.646.049 1.288.11 1.927.184 1.1.128 1.907 1.077 1.907 2.185V19.5a2.25 2.25 0 01-2.25 2.25H6.75A2.25 2.25 0 014.5 19.5V6.257c0-1.108.806-2.057 1.907-2.185a48.208 48.208 0 011.927-.184" />
                            </svg>
                            Copy
                        </>
                    )}
                </button>
            </div>
        </div>
    );
}

/**
 * Business/ApiTokens — zarządzanie API tokenami.
 *
 * Props z kontrolera:
 *   tokens    — ApiToken[] (bez token_hash)
 *   newToken  — string|null (jednorazowy flash)
 */
export default function ApiTokens({ tokens = [], newToken = null, client }) {
    const { flash } = usePage().props;
    const { form, create, copied, copyToClipboard } = useApiTokens();

    const displayToken = newToken ?? flash?.new_token ?? null;
    const successMsg = flash?.success;

    return (
        <PortalLayout client={client}>
            <Head title="API Tokens" />

            <div className="max-w-3xl mx-auto space-y-6">

                <div>
                    <h1 className="text-2xl font-bold text-gray-900">API Tokens</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Allow external forms and tools to capture leads into your account.
                    </p>
                </div>

                    {/* Flash success (token revoked etc.) */}
                    {successMsg && !displayToken && (
                        <div className="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                            {successMsg}
                        </div>
                    )}

                    {/* New token alert — jednorazowy */}
                    {displayToken && (
                        <NewTokenAlert
                            token={displayToken}
                            onCopy={copyToClipboard}
                            copied={copied}
                        />
                    )}

                    {/* Info box */}
                    <div className="rounded-xl border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-900/20 p-4">
                        <div className="flex gap-3">
                            <svg className="h-5 w-5 text-blue-500 dark:text-blue-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                            </svg>
                            <div className="text-sm text-blue-700 dark:text-blue-300 space-y-1">
                                <p className="font-medium">How to use API tokens</p>
                                <p>Send leads to your account from any external form or tool using:</p>
                                <code className="block mt-1 text-xs bg-blue-100 dark:bg-blue-900/40 rounded px-2 py-1 font-mono">
                                    POST {window.location.origin}/api/v1/leads<br />
                                    Authorization: Bearer {'<your-token>'}
                                </code>
                            </div>
                        </div>
                    </div>

                    {/* Token list */}
                    <section>
                        <h3 className="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Your Tokens ({tokens.length})
                        </h3>

                        {tokens.length === 0 ? (
                            <div className="rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700 p-8 text-center">
                                <svg className="mx-auto h-10 w-10 text-gray-300 dark:text-gray-600 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.5}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" />
                                </svg>
                                <p className="text-sm text-gray-500 dark:text-gray-400">
                                    No API tokens yet. Create your first token below.
                                </p>
                            </div>
                        ) : (
                            <div className="space-y-3">
                                {tokens.map(token => (
                                    <ApiTokenCard key={token.id} token={token} />
                                ))}
                            </div>
                        )}
                    </section>

                    {/* Create form */}
                    <section className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 p-5">
                        <h3 className="text-base font-semibold text-gray-900 dark:text-white mb-4">
                            Create New Token
                        </h3>

                        <form onSubmit={create} className="flex flex-col sm:flex-row gap-3">
                            <div className="flex-1">
                                <InputLabel htmlFor="token-name" value="Token name" className="sr-only" />
                                <TextInput
                                    id="token-name"
                                    type="text"
                                    value={form.data.name}
                                    onChange={(e) => form.setData('name', e.target.value)}
                                    placeholder="e.g. Website Contact Form, Zapier Integration"
                                    className="w-full"
                                    required
                                    maxLength={255}
                                />
                                <InputError message={form.errors.name} className="mt-1" />
                            </div>
                            <PrimaryButton
                                type="submit"
                                disabled={form.processing || !form.data.name.trim()}
                                className="whitespace-nowrap"
                            >
                                {form.processing ? 'Creating…' : 'Create Token'}
                            </PrimaryButton>
                        </form>

                        <p className="mt-2 text-xs text-gray-400 dark:text-gray-500">
                            The token will be shown once after creation. Store it securely.
                        </p>
                    </section>
            </div>
        </PortalLayout>
    );
}
