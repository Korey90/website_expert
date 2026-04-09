import { Head, Link } from '@inertiajs/react';
import PortalLayout from '@/Layouts/PortalLayout';

const ACTIVITY_COLORS = {
    lp_captured:  'bg-brand-100 text-brand-700 dark:bg-brand-900/30 dark:text-brand-300',
    note_added:   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
    stage_moved:  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-300',
    assigned:     'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-300',
    won:          'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
    lost:         'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
};

function Badge({ type }) {
    const cls = ACTIVITY_COLORS[type] ?? 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300';
    return (
        <span className={`inline-block rounded-full px-2 py-0.5 text-xs font-semibold ${cls}`}>
            {type?.replace(/_/g, ' ')}
        </span>
    );
}

function Section({ title, children }) {
    return (
        <div className="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm overflow-hidden">
            <div className="px-5 py-4 border-b border-gray-100 dark:border-gray-700">
                <h3 className="font-semibold text-sm text-gray-900 dark:text-white">{title}</h3>
            </div>
            <div className="px-5 py-4">{children}</div>
        </div>
    );
}

function FieldRow({ label, value }) {
    if (!value) return null;
    return (
        <div className="flex flex-col sm:flex-row sm:items-baseline gap-1 py-2 border-b border-gray-50 dark:border-gray-700 last:border-0">
            <span className="w-40 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">{label}</span>
            <span className="text-sm text-gray-900 dark:text-white break-all">{value}</span>
        </div>
    );
}

export default function Show({ lead, client }) {
    const hasUtm = lead.utm && Object.keys(lead.utm).length > 0;
    const hasFormData = lead.form_data && Object.keys(lead.form_data).length > 0;
    const backRoute = lead.landing_page
        ? route('landing-pages.show', lead.landing_page.id)
        : route('landing-pages.index');

    return (
        <PortalLayout client={client}>
            <Head title={`Lead — ${lead.contact?.name ?? lead.title ?? '#' + lead.id}`} />

            <div className="py-8">
                <div className="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Header */}
                    <div className="flex items-start gap-3">
                        <Link
                            href={backRoute}
                            className="mt-1 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                            </svg>
                        </Link>
                        <div>
                            <h2 className="font-bold text-xl text-gray-900 dark:text-white">
                                {lead.contact?.name ?? lead.title ?? `Lead #${lead.id}`}
                            </h2>
                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-0.5">
                                Captured: {lead.created_at}
                                {lead.landing_page && (
                                    <>
                                        {' · '}
                                        <Link
                                            href={route('landing-pages.show', lead.landing_page.id)}
                                            className="text-brand-600 dark:text-brand-400 hover:underline"
                                        >
                                            {lead.landing_page.title}
                                        </Link>
                                    </>
                                )}
                            </p>
                        </div>
                    </div>

                    {/* Contact data */}
                    <Section title="Contact">
                        <FieldRow label="Name"  value={lead.contact?.name} />
                        <FieldRow label="Email" value={lead.contact?.email} />
                        <FieldRow label="Phone" value={lead.contact?.phone} />
                        <FieldRow label="Source" value={lead.source ?? (hasUtm ? lead.utm.utm_source : null) ?? 'Direct'} />
                    </Section>

                    {/* Form data */}
                    {hasFormData && (
                        <Section title="Form answers">
                            {Object.entries(lead.form_data).map(([key, val]) => (
                                <FieldRow key={key} label={key.replace(/_/g, ' ')} value={String(val)} />
                            ))}
                        </Section>
                    )}

                    {/* UTM tracking */}
                    {hasUtm && (
                        <Section title="Traffic source (UTM)">
                            {Object.entries(lead.utm).map(([key, val]) => (
                                <FieldRow key={key} label={key.replace('utm_', '')} value={val} />
                            ))}
                        </Section>
                    )}

                    {/* Notes */}
                    {lead.notes && (
                        <Section title="Notes">
                            <p className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-wrap">{lead.notes}</p>
                        </Section>
                    )}

                    {/* Activity timeline */}
                    {lead.activities && lead.activities.length > 0 && (
                        <Section title="Activity">
                            <ol className="space-y-4">
                                {lead.activities.map((activity) => (
                                    <li key={activity.id} className="flex gap-3">
                                        <div className="flex flex-col items-center">
                                            <span className="h-2.5 w-2.5 rounded-full bg-brand-500 mt-1 shrink-0" />
                                            <span className="flex-1 w-px bg-gray-200 dark:bg-gray-700 mt-1" />
                                        </div>
                                        <div className="pb-4 flex-1">
                                            <div className="flex items-center gap-2 flex-wrap">
                                                <Badge type={activity.type} />
                                                <span className="text-xs text-gray-400 dark:text-gray-500">{activity.created_at}</span>
                                            </div>
                                            {activity.notes && (
                                                <p className="mt-1 text-sm text-gray-700 dark:text-gray-300">{activity.notes}</p>
                                            )}
                                            {activity.metadata && Object.keys(activity.metadata).length > 0 && (
                                                <div className="mt-1 flex flex-wrap gap-2">
                                                    {Object.entries(activity.metadata).map(([k, v]) => (
                                                        <span key={k} className="text-xs bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400 rounded px-1.5 py-0.5">
                                                            {k}: {String(v)}
                                                        </span>
                                                    ))}
                                                </div>
                                            )}
                                        </div>
                                    </li>
                                ))}
                            </ol>
                        </Section>
                    )}

                </div>
            </div>
        </PortalLayout>
    );
}
