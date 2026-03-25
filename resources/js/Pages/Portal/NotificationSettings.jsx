import PortalLayout from '@/Layouts/PortalLayout';
import { Link, useForm, usePage } from '@inertiajs/react';

const categories = [
    {
        key: 'notify_email_transactional',
        icon: '🧾',
        title: 'Transactional emails',
        description: 'Invoices, payment confirmations, quote deliveries, and contract documents.',
        warning: 'Disabling this means you may not receive invoices or payment receipts.',
    },
    {
        key: 'notify_email_projects',
        icon: '📁',
        title: 'Project updates',
        description: 'Project status changes, messages from your project team, and milestone notifications.',
        warning: null,
    },
    {
        key: 'notify_email_marketing',
        icon: '📣',
        title: 'Marketing & newsletters',
        description: 'Promotional emails, newsletters, and automated follow-up messages.',
        warning: null,
    },
    {
        key: 'notify_sms',
        icon: '📱',
        title: 'SMS notifications',
        description: 'Text messages for payment confirmations and important alerts.',
        warning: null,
    },
];

function Toggle({ enabled, onChange }) {
    return (
        <button
            type="button"
            onClick={() => onChange(!enabled)}
            className={`relative inline-flex h-6 w-11 flex-shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 ${
                enabled ? 'bg-red-600' : 'bg-gray-300'
            }`}
            role="switch"
            aria-checked={enabled}
        >
            <span
                className={`pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out ${
                    enabled ? 'translate-x-5' : 'translate-x-0'
                }`}
            />
        </button>
    );
}

export default function NotificationSettings({ client, prefs }) {
    const { props } = usePage();
    const flash = props.flash ?? {};

    const { data, setData, post, processing } = useForm({
        notify_email_transactional: prefs.notify_email_transactional,
        notify_email_projects:      prefs.notify_email_projects,
        notify_email_marketing:     prefs.notify_email_marketing,
        notify_sms:                 prefs.notify_sms,
    });

    function handleSubmit(e) {
        e.preventDefault();
        post(route('portal.settings.notifications.update'));
    }

    const lastUpdated = prefs.updated_at
        ? new Date(prefs.updated_at).toLocaleDateString('en-GB', {
              day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit',
          })
        : null;

    return (
        <PortalLayout client={client}>
            <div className="max-w-2xl mx-auto space-y-6">

                {/* Back */}
                <Link
                    href={route('portal.dashboard')}
                    className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700"
                >
                    ← Back to Dashboard
                </Link>

                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-gray-900">Communication Preferences</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        Choose how you want us to contact you. Changes take effect immediately.
                    </p>
                    {lastUpdated && (
                        <p className="mt-1 text-xs text-gray-400">Last updated: {lastUpdated}</p>
                    )}
                </div>

                {/* Success banner */}
                {flash.success && (
                    <div className="flex items-center gap-3 rounded-xl border border-green-200 bg-green-50 px-5 py-3">
                        <span>✅</span>
                        <p className="text-sm font-medium text-green-700">{flash.success}</p>
                    </div>
                )}

                {/* Form */}
                <form onSubmit={handleSubmit} className="space-y-4">
                    {categories.map((cat) => {
                        const enabled = data[cat.key];
                        return (
                            <div
                                key={cat.key}
                                className="bg-white rounded-xl border border-gray-200 shadow-sm px-6 py-5"
                            >
                                <div className="flex items-start justify-between gap-4">
                                    <div className="flex items-start gap-3 flex-1">
                                        <span className="text-2xl mt-0.5">{cat.icon}</span>
                                        <div>
                                            <p className="text-sm font-semibold text-gray-900">{cat.title}</p>
                                            <p className="text-sm text-gray-500 mt-0.5">{cat.description}</p>
                                            {!enabled && cat.warning && (
                                                <p className="mt-2 text-xs text-amber-700 bg-amber-50 border border-amber-200 rounded-lg px-3 py-1.5">
                                                    ⚠️ {cat.warning}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                    <div className="flex-shrink-0 pt-0.5">
                                        <Toggle
                                            enabled={enabled}
                                            onChange={(val) => setData(cat.key, val)}
                                        />
                                    </div>
                                </div>
                            </div>
                        );
                    })}

                    <div className="flex items-center justify-between pt-2">
                        <p className="text-xs text-gray-400 max-w-xs">
                            These preferences are stored securely and can be changed at any time.
                        </p>
                        <button
                            type="submit"
                            disabled={processing}
                            className="inline-flex items-center gap-2 rounded-lg bg-red-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm hover:bg-red-700 disabled:opacity-60 transition-colors"
                        >
                            {processing ? (
                                <>
                                    <svg className="animate-spin h-4 w-4" fill="none" viewBox="0 0 24 24">
                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4" />
                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z" />
                                    </svg>
                                    Saving…
                                </>
                            ) : 'Save preferences'}
                        </button>
                    </div>
                </form>

                {/* GDPR note */}
                <div className="rounded-xl border border-gray-100 bg-gray-50 px-6 py-4">
                    <p className="text-xs text-gray-500 leading-relaxed">
                        <strong>Your privacy matters.</strong> We process your data in accordance with UK GDPR. Transactional
                        communications (invoices, payment receipts) may still be sent where required by our contractual
                        obligation. You can request full deletion of your data by contacting us at{' '}
                        <a href={`mailto:${window.appConfig?.contactEmail ?? 'hello@websiteexpert.co.uk'}`} className="underline text-gray-700">
                            hello@websiteexpert.co.uk
                        </a>.
                    </p>
                </div>

            </div>
        </PortalLayout>
    );
}
