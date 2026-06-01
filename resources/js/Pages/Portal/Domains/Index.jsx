import EmptyState from '@/Components/Shared/EmptyState';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link, usePage } from '@inertiajs/react';

const T = {
    title:          { en: 'My Domains',           pl: 'Moje domeny',                    pt: 'Os meus dom\u00ednios' },
    registerNew:    { en: 'Register a Domain',    pl: 'Zarejestruj dom\u0119n\u0119',   pt: 'Registar dom\u00ednio' },
    domain:         { en: 'Domain',               pl: 'Domena',                          pt: 'Dom\u00ednio' },
    status:         { en: 'Status',               pl: 'Status',                          pt: 'Estado' },
    expires:        { en: 'Expires',              pl: 'Wyga\u015bnie',                   pt: 'Expira' },
    autoRenew:      { en: 'Auto-Renew',           pl: 'Auto-odn.',                       pt: 'Auto-renov.' },
    view:           { en: 'View \u2192',          pl: 'Szczeg\u00f3\u0142y \u2192',     pt: 'Ver \u2192' },
    expiringSoon:   { en: 'Expiring soon',        pl: 'Wyga\u015bnie wkr\u00f3tce',     pt: 'Expira em breve' },
    emptyTitle:     { en: 'No domains yet',       pl: 'Brak domen',                      pt: 'Sem dom\u00ednios' },
    emptyDesc:      { en: 'Register your first domain to get started.',
                      pl: 'Zarejestruj pierwsz\u0105 dom\u0119n\u0119, aby zacz\u0105\u0107.',
                      pt: 'Registe o seu primeiro dom\u00ednio para come\u00e7ar.' },
    yes:            { en: 'Yes', pl: 'Tak', pt: 'Sim' },
    no:             { en: 'No',  pl: 'Nie', pt: 'N\u00e3o' },
    daysLeft:       { en: (n) => `${n}d left`,  pl: (n) => `${n} dni`,  pt: (n) => `${n}d` },
};

const STATUS_COLORS = {
    active:       'bg-green-100 text-green-800',
    pending:      'bg-yellow-100 text-yellow-800',
    expired:      'bg-red-100 text-red-800',
    transferred:  'bg-blue-100 text-blue-800',
    cancelled:    'bg-gray-100 text-gray-500',
};

function daysUntil(dateStr) {
    if (!dateStr) return null;
    return Math.ceil((new Date(dateStr) - Date.now()) / 86_400_000);
}

export default function DomainsIndex({ client, domains }) {
    const { locale } = usePage().props;
    const t = (key, ...args) => {
        const v = T[key]?.[locale] ?? T[key]?.en;
        return typeof v === 'function' ? v(...args) : (v ?? key);
    };

    return (
        <PortalLayout client={client}>
            <Head title={t('title')} />
            <div className="max-w-5xl mx-auto space-y-6">
                <div className="flex items-center justify-between">
                    <h1 className="text-2xl font-bold text-gray-900">{t('title')}</h1>
                    <Link
                        href={route('domains.check')}
                        className="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 active:scale-95 transition-all"
                    >
                        + {t('registerNew')}
                    </Link>
                </div>

                {domains.length === 0 ? (
                    <EmptyState
                        icon="🌐"
                        title={t('emptyTitle')}
                        description={t('emptyDesc')}
                    />
                ) : (
                    <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                        <table className="min-w-full divide-y divide-gray-100">
                            <thead className="bg-gray-50">
                                <tr>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('domain')}</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('status')}</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('expires')}</th>
                                    <th className="px-5 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('autoRenew')}</th>
                                    <th className="px-5 py-3" />
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {domains.map(d => {
                                    const days = daysUntil(d.expires_at);
                                    const expiringSoon = days !== null && days <= 30 && days >= 0 && d.status === 'active';
                                    return (
                                        <tr key={d.id} className={`hover:bg-gray-50 ${expiringSoon ? 'bg-orange-50' : ''}`}>
                                            <td className="px-5 py-3">
                                                <div className="flex items-center gap-2">
                                                    <span className="text-sm font-semibold text-gray-900">{d.full_domain}</span>
                                                    {expiringSoon && (
                                                        <span className="text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">
                                                            {t('expiringSoon')}
                                                        </span>
                                                    )}
                                                </div>
                                            </td>
                                            <td className="px-5 py-3">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${STATUS_COLORS[d.status] ?? 'bg-gray-100 text-gray-700'}`}>
                                                    {d.status}
                                                </span>
                                            </td>
                                            <td className="px-5 py-3 text-sm text-gray-600">
                                                {d.expires_at ? (
                                                    <span className={expiringSoon ? 'text-orange-700 font-semibold' : ''}>
                                                        {d.expires_at}
                                                        {days !== null && days >= 0 && days <= 60 && (
                                                            <span className="ml-1.5 text-xs text-gray-400">({t('daysLeft', days)})</span>
                                                        )}
                                                    </span>
                                                ) : '—'}
                                            </td>
                                            <td className="px-5 py-3 text-sm text-gray-600">
                                                {d.auto_renew ? t('yes') : t('no')}
                                            </td>
                                            <td className="px-5 py-3 text-right">
                                                <Link
                                                    href={route('portal.domains.show', d.id)}
                                                    className="inline-flex items-center px-3 py-1.5 bg-gray-100 text-gray-700 text-xs font-medium rounded-lg hover:bg-gray-200 transition-colors"
                                                >
                                                    {t('view')}
                                                </Link>
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>
                )}
            </div>
        </PortalLayout>
    );
}
