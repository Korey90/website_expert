import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import SourceBadge from '@/Components/Lead/SourceBadge';
import ConsentBadge from '@/Components/Lead/ConsentBadge';
import LeadTimeline from '@/Components/Lead/LeadTimeline';
import SourceAttribution from '@/Components/Lead/SourceAttribution';
import { useLeads } from '@/Hooks/useLeads';

const TABS = [
    { key: 'details',      label: 'Details' },
    { key: 'timeline',     label: 'Timeline' },
    { key: 'source',       label: 'Source Attribution' },
    { key: 'gdpr',         label: 'GDPR Consent' },
];

function InfoRow({ label, value }) {
    if (!value) return null;
    return (
        <div className="flex flex-col sm:flex-row sm:gap-4 py-2.5 border-b border-gray-100 dark:border-gray-700 last:border-0">
            <dt className="text-xs font-medium text-gray-500 dark:text-gray-400 sm:w-36 shrink-0">{label}</dt>
            <dd className="mt-0.5 sm:mt-0 text-sm text-gray-900 dark:text-white">{value}</dd>
        </div>
    );
}

function StageBadge({ stage }) {
    if (!stage) return null;
    return (
        <span className="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300">
            {stage.name}
        </span>
    );
}

function WonBadge() {
    return (
        <span className="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
            Won
        </span>
    );
}

function LostBadge({ reason }) {
    return (
        <span className="inline-flex items-center gap-1 rounded-full px-2.5 py-1 text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300" title={reason}>
            <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2.5}>
                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
            Lost
        </span>
    );
}

/**
 * Leads/Show — szczegóły leada z zakładkami: Details, Timeline, Source, GDPR.
 *
 * Props z kontrolera:
 *   lead    — pełny model z relacjami (client, stage, assignedTo, leadSource, consent, activities)
 *   stages  — PipelineStage[]
 *   users   — User[]
 */
export default function Show({ lead, stages = [], users = [] }) {
    const { flash } = usePage().props;
    const [activeTab, setActiveTab] = useState('details');
    const [lostReason, setLostReason] = useState('');
    const [showLostForm, setShowLostForm] = useState(false);
    const { processing, error, assign, changeStage, markWon, markLost } = useLeads(lead.id);

    const isWon  = !!lead.won_at;
    const isLost = !!lead.lost_at;

    const handleMarkLost = () => {
        if (!lostReason.trim()) return;
        markLost(lostReason);
        setShowLostForm(false);
        setLostReason('');
    };

    const formattedValue = lead.value
        ? new Intl.NumberFormat('en-GB', { style: 'currency', currency: lead.currency ?? 'GBP' }).format(lead.value)
        : null;

    return (
        <AuthenticatedLayout
            header={
                <div className="flex items-center gap-3 flex-wrap">
                    <Link
                        href="/admin"
                        className="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition"
                    >
                        <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                            <path strokeLinecap="round" strokeLinejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                        </svg>
                    </Link>
                    <div className="flex items-center gap-2 flex-wrap">
                        <h2 className="font-display text-xl font-semibold text-gray-900 dark:text-white">
                            {lead.title ?? lead.name ?? lead.email}
                        </h2>
                        {isWon ? <WonBadge /> : isLost ? <LostBadge reason={lead.lost_reason} /> : <StageBadge stage={lead.stage} />}
                        {lead.leadSource && <SourceBadge type={lead.leadSource.type} />}
                    </div>
                    {formattedValue && (
                        <span className="ml-auto text-lg font-bold text-gray-900 dark:text-white">{formattedValue}</span>
                    )}
                </div>
            }
        >
            <Head title={`Lead — ${lead.title ?? lead.email}`} />

            <div className="py-6">
                <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 space-y-6">

                    {/* Flash */}
                    {flash?.success && (
                        <div className="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 px-4 py-3 text-sm text-green-700 dark:text-green-300">
                            {flash.success}
                        </div>
                    )}
                    {error && (
                        <div className="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 px-4 py-3 text-sm text-red-700 dark:text-red-300">
                            {error}
                        </div>
                    )}

                    {/* Action bar */}
                    {!isWon && !isLost && (
                        <div className="flex flex-wrap gap-2">
                            {/* Assign */}
                            <select
                                onChange={(e) => e.target.value && assign(e.target.value)}
                                defaultValue=""
                                disabled={processing}
                                className="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 px-3 py-2 shadow-sm focus:ring-2 focus:ring-brand-500"
                            >
                                <option value="">Assign to…</option>
                                {users.map(u => (
                                    <option key={u.id} value={u.id}
                                        selected={lead.assigned_to === u.id ? true : undefined}
                                    >
                                        {u.name}
                                    </option>
                                ))}
                            </select>

                            {/* Change Stage */}
                            <select
                                onChange={(e) => e.target.value && changeStage(e.target.value)}
                                defaultValue={lead.pipeline_stage_id ?? ''}
                                disabled={processing}
                                className="rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-700 dark:text-gray-300 px-3 py-2 shadow-sm focus:ring-2 focus:ring-brand-500"
                            >
                                <option value="">Change Stage…</option>
                                {stages.map(s => (
                                    <option key={s.id} value={s.id}>{s.name}</option>
                                ))}
                            </select>

                            {/* Mark Won */}
                            <button
                                type="button"
                                onClick={markWon}
                                disabled={processing}
                                className="rounded-xl bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 transition"
                            >
                                Mark Won
                            </button>

                            {/* Mark Lost */}
                            <button
                                type="button"
                                onClick={() => setShowLostForm(v => !v)}
                                disabled={processing}
                                className="rounded-xl border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-semibold px-4 py-2 transition"
                            >
                                Mark Lost
                            </button>
                        </div>
                    )}

                    {/* Lost reason form */}
                    {showLostForm && (
                        <div className="flex gap-2">
                            <input
                                type="text"
                                value={lostReason}
                                onChange={(e) => setLostReason(e.target.value)}
                                placeholder="Reason for losing this lead…"
                                className="flex-1 rounded-xl border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm px-3 py-2 focus:ring-2 focus:ring-brand-500 dark:text-white"
                            />
                            <button
                                type="button"
                                onClick={handleMarkLost}
                                disabled={!lostReason.trim() || processing}
                                className="rounded-xl bg-red-600 hover:bg-red-700 disabled:opacity-50 text-white text-sm font-semibold px-4 py-2 transition"
                            >
                                Confirm
                            </button>
                        </div>
                    )}

                    {/* Tabs */}
                    <div className="rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 overflow-hidden">
                        <div className="flex border-b border-gray-200 dark:border-gray-700 overflow-x-auto">
                            {TABS.map(tab => (
                                <button
                                    key={tab.key}
                                    type="button"
                                    onClick={() => setActiveTab(tab.key)}
                                    className={`flex-shrink-0 px-5 py-3.5 text-sm font-medium transition border-b-2 ${
                                        activeTab === tab.key
                                            ? 'border-brand-500 text-brand-600 dark:text-brand-400'
                                            : 'border-transparent text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200'
                                    }`}
                                >
                                    {tab.label}
                                </button>
                            ))}
                        </div>

                        <div className="p-5">
                            {/* Details */}
                            {activeTab === 'details' && (
                                <dl className="space-y-0">
                                    <InfoRow label="Email"    value={lead.email} />
                                    <InfoRow label="Name"     value={lead.name} />
                                    <InfoRow label="Phone"    value={lead.phone} />
                                    <InfoRow label="Company"  value={lead.company} />
                                    <InfoRow label="NIP"      value={lead.nip} />
                                    <InfoRow label="Project"  value={lead.project_type} />
                                    <InfoRow label="Value"    value={formattedValue} />
                                    <InfoRow label="Client"   value={lead.client?.company_name} />
                                    <InfoRow label="Assigned" value={lead.assignedTo?.name} />
                                    <InfoRow label="Close Date" value={lead.expected_close_date} />
                                    <InfoRow label="Source"   value={lead.source} />
                                    {lead.notes && (
                                        <div className="pt-3">
                                            <dt className="text-xs font-medium text-gray-500 dark:text-gray-400 mb-1">Notes</dt>
                                            <dd className="text-sm text-gray-700 dark:text-gray-300 whitespace-pre-line">{lead.notes}</dd>
                                        </div>
                                    )}
                                </dl>
                            )}

                            {/* Timeline */}
                            {activeTab === 'timeline' && (
                                <LeadTimeline activities={lead.activities ?? []} />
                            )}

                            {/* Source Attribution */}
                            {activeTab === 'source' && (
                                <SourceAttribution source={lead.leadSource ?? null} lead={lead} />
                            )}

                            {/* GDPR Consent */}
                            {activeTab === 'gdpr' && (
                                <ConsentBadge consent={lead.consent ?? null} showDetails={true} />
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
