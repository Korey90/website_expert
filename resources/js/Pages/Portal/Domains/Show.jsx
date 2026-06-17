import Modal from '@/Components/Modal';
import useCurrency from '@/Hooks/useCurrency';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const T = {
    backToDomains:    { en: 'Back to Domains',        pl: 'Powrót do domen',         pt: 'Voltar aos domínios' },
    domainDetails:    { en: 'Domain Details',          pl: 'Szczegóły domeny',        pt: 'Detalhes do domínio' },
    renewDomain:      { en: 'Renew Domain',            pl: 'Odnowić domenę',          pt: 'Renovar domínio' },
    registration:     { en: 'Registration',            pl: 'Rejestracja',             pt: 'Registo' },
    status:           { en: 'Status',                  pl: 'Status',                  pt: 'Estado' },
    registeredOn:     { en: 'Registered on',           pl: 'Zarejestrowana',          pt: 'Registado em' },
    expiresOn:        { en: 'Expires on',              pl: 'Wygaśnie',                pt: 'Expira em' },
    autoRenew:        { en: 'Auto-Renew',              pl: 'Auto-odnowienie',         pt: 'Auto-renovação' },
    whoisPrivacy:     { en: 'WHOIS Privacy',           pl: 'Ochrona WHOIS',           pt: 'Privacidade WHOIS' },
    provider:         { en: 'Provider',                pl: 'Dostawca',                pt: 'Fornecedor' },
    nameservers:      { en: 'Nameservers',             pl: 'Serwery nazw',            pt: 'Servidores de nome' },
    noNameservers:    { en: 'No nameservers configured yet.', pl: 'Brak skonfigurowanych serwerów nazw.', pt: 'Nenhum servidor de nome configurado.' },
    editNameservers:  { en: 'Edit Nameservers',        pl: 'Edytuj serwery nazw',     pt: 'Editar servidores de nome' },
    manageDns:        { en: 'Manage DNS →',            pl: 'Zarządzaj DNS →',         pt: 'Gerir DNS →' },
    renewalHistory:   { en: 'Renewal History',         pl: 'Historia odnowień',       pt: 'Histórico de renovações' },
    noRenewals:       { en: 'No renewals yet.',        pl: 'Brak odnowień.',          pt: 'Sem renovações.' },
    date:             { en: 'Date',                    pl: 'Data',                    pt: 'Data' },
    years:            { en: 'Years',                   pl: 'Lata',                    pt: 'Anos' },
    amount:           { en: 'Amount',                  pl: 'Kwota',                   pt: 'Valor' },
    renewStatus:      { en: 'Status',                  pl: 'Status',                  pt: 'Estado' },
    enabled:          { en: 'Enabled',                 pl: 'Włączona',                pt: 'Ativada' },
    disabled:         { en: 'Disabled',                pl: 'Wyłączona',               pt: 'Desativada' },
    expiringSoon:     { en: 'Expiring soon',           pl: 'Wygaśnie wkrótce',        pt: 'Expira em breve' },
    save:             { en: 'Save',                    pl: 'Zapisz',                  pt: 'Guardar' },
    cancel:           { en: 'Cancel',                  pl: 'Anuluj',                  pt: 'Cancelar' },
    addNameserver:    { en: 'Add nameserver',          pl: 'Dodaj serwer nazw',       pt: 'Adicionar servidor de nome' },
    nsUpdated:        { en: 'Nameservers updated.',    pl: 'Serwery nazw zaktualizowane.', pt: 'Servidores de nome atualizados.' },
};

const STATUS_COLORS = {
    active:      'bg-green-100 text-green-800',
    pending:     'bg-yellow-100 text-yellow-800',
    expired:     'bg-red-100 text-red-800',
    transferred: 'bg-blue-100 text-blue-800',
    cancelled:   'bg-gray-100 text-gray-500',
};

const RENEWAL_STATUS_COLORS = {
    pending:   'bg-yellow-100 text-yellow-800',
    paid:      'bg-blue-100 text-blue-800',
    completed: 'bg-green-100 text-green-800',
    failed:    'bg-red-100 text-red-800',
};

function Row({ label, children }) {
    return (
        <div className="flex items-start justify-between py-3 border-b border-gray-100 last:border-b-0">
            <span className="text-sm text-gray-500 w-40 shrink-0">{label}</span>
            <span className="text-sm font-medium text-gray-900 text-right">{children}</span>
        </div>
    );
}

export default function DomainsShow({ client, domain, renewals }) {
    const { locale, flash } = usePage().props;
    const { formatCurrency } = useCurrency();
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? key;

    const days = domain.expires_at
        ? Math.ceil((new Date(domain.expires_at) - Date.now()) / 86_400_000)
        : null;
    const expiringSoon = days !== null && days <= 30 && days >= 0 && domain.status === 'active';

    // ── Nameserver modal ──────────────────────────────────────────────────────
    const [showNsModal, setShowNsModal] = useState(false);
    const { data, setData, put, processing, errors, reset } = useForm({
        nameservers: domain.nameservers?.length ? [...domain.nameservers] : [''],
    });

    function addNs() {
        if (data.nameservers.length < 5) {
            setData('nameservers', [...data.nameservers, '']);
        }
    }

    function removeNs(i) {
        if (data.nameservers.length > 1) {
            setData('nameservers', data.nameservers.filter((_, idx) => idx !== i));
        }
    }

    function updateNs(i, val) {
        const updated = [...data.nameservers];
        updated[i] = val;
        setData('nameservers', updated);
    }

    function submitNs(e) {
        e.preventDefault();
        put(route('portal.domains.nameservers.update', domain.id), {
            onSuccess: () => { setShowNsModal(false); },
        });
    }

    function openNsModal() {
        setData('nameservers', domain.nameservers?.length ? [...domain.nameservers] : ['']);
        setShowNsModal(true);
    }

    return (
        <PortalLayout client={client}>
            <Head title={domain.full_domain} />
            <div className="max-w-3xl mx-auto space-y-6">

                {/* Flash success */}
                {flash?.success && (
                    <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {/* Header */}
                <div>
                    <Link
                        href={route('portal.domains.index')}
                        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        {t('backToDomains')}
                    </Link>
                    <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{domain.full_domain}</h1>
                            {expiringSoon && (
                                <span className="mt-1 inline-flex text-xs bg-orange-100 text-orange-700 px-2 py-0.5 rounded-full font-medium">
                                    ⚠ {t('expiringSoon')}
                                </span>
                            )}
                        </div>
                        {domain.status === 'active' && (
                            <Link
                                href={`${route('portal.domains.order')}?domain=${encodeURIComponent(domain.name)}&tld=${encodeURIComponent(domain.tld)}&action=renew`}
                                className="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 active:scale-95 transition-all whitespace-nowrap"
                            >
                                {t('renewDomain')} →
                            </Link>
                        )}
                    </div>
                </div>

                {/* Registration info */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                    <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-2">{t('registration')}</h2>
                    <Row label={t('status')}>
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize ${STATUS_COLORS[domain.status] ?? 'bg-gray-100 text-gray-700'}`}>
                            {domain.status}
                        </span>
                    </Row>
                    <Row label={t('registeredOn')}>{domain.registered_at ?? '—'}</Row>
                    <Row label={t('expiresOn')}>
                        <span className={expiringSoon ? 'text-orange-700 font-semibold' : ''}>
                            {domain.expires_at ?? '—'}
                            {days !== null && days >= 0 && days <= 60 && (
                                <span className="ml-1.5 text-xs text-gray-400">({days}d)</span>
                            )}
                        </span>
                    </Row>
                    <Row label={t('autoRenew')}>
                        <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${domain.auto_renew ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'}`}>
                            {domain.auto_renew ? t('enabled') : t('disabled')}
                        </span>
                    </Row>
                    <Row label={t('whoisPrivacy')}>
                        <span className={`text-xs font-medium px-2 py-0.5 rounded-full ${domain.whois_privacy ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-500'}`}>
                            {domain.whois_privacy ? t('enabled') : t('disabled')}
                        </span>
                    </Row>
                    {domain.provider && (
                        <Row label={t('provider')}>{domain.provider}</Row>
                    )}
                </div>

                {/* Nameservers */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                    <div className="flex items-center justify-between mb-3">
                        <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide">{t('nameservers')}</h2>
                        <div className="flex items-center gap-3">
                            <Link
                                href={route('portal.domains.dns.index', domain.id)}
                                className="text-xs font-medium text-indigo-600 hover:text-indigo-800 transition-colors"
                            >
                                {t('manageDns')}
                            </Link>
                            <button
                                onClick={openNsModal}
                                className="text-xs font-medium text-gray-600 hover:text-gray-900 border border-gray-300 rounded-md px-2.5 py-1 hover:bg-gray-50 transition-colors"
                            >
                                {t('editNameservers')}
                            </button>
                        </div>
                    </div>
                    {domain.nameservers && domain.nameservers.length > 0 ? (
                        <ul className="space-y-2">
                            {domain.nameservers.map((ns, i) => (
                                <li key={i} className="flex items-center gap-2 text-sm font-mono text-gray-800 bg-gray-50 px-3 py-2 rounded-lg">
                                    <span className="text-gray-400 text-xs w-5 text-right shrink-0">NS{i + 1}</span>
                                    {ns}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p className="text-sm text-gray-400">{t('noNameservers')}</p>
                    )}
                </div>

                {/* Renewal history */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 px-6 py-4">
                    <h2 className="text-sm font-semibold text-gray-700 uppercase tracking-wide mb-3">{t('renewalHistory')}</h2>
                    {renewals.length === 0 ? (
                        <p className="text-sm text-gray-400">{t('noRenewals')}</p>
                    ) : (
                        <table className="min-w-full text-sm">
                            <thead>
                                <tr className="border-b border-gray-100">
                                    <th className="pb-2 text-left text-xs font-medium text-gray-500 uppercase">{t('date')}</th>
                                    <th className="pb-2 text-left text-xs font-medium text-gray-500 uppercase">{t('years')}</th>
                                    <th className="pb-2 text-left text-xs font-medium text-gray-500 uppercase">{t('amount')}</th>
                                    <th className="pb-2 text-left text-xs font-medium text-gray-500 uppercase">{t('renewStatus')}</th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-50">
                                {renewals.map(r => (
                                    <tr key={r.id}>
                                        <td className="py-2 text-gray-700">{r.due_date ?? '—'}</td>
                                        <td className="py-2 text-gray-700">{r.years}</td>
                                        <td className="py-2 text-gray-700">{formatCurrency(r.amount, r.currency)}</td>
                                        <td className="py-2">
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium capitalize ${RENEWAL_STATUS_COLORS[r.status] ?? 'bg-gray-100 text-gray-700'}`}>
                                                {r.status}
                                            </span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

            </div>

            {/* Edit Nameservers Modal */}
            <Modal show={showNsModal} maxWidth="lg" onClose={() => setShowNsModal(false)}>
                <form onSubmit={submitNs} className="p-6 space-y-4">
                    <h3 className="text-base font-semibold text-gray-900">{t('editNameservers')}</h3>

                    <div className="space-y-2">
                        {data.nameservers.map((ns, i) => (
                            <div key={i} className="flex items-center gap-2">
                                <span className="text-xs text-gray-400 w-8 text-right">NS{i + 1}</span>
                                <input
                                    type="text"
                                    value={ns}
                                    onChange={e => updateNs(i, e.target.value)}
                                    placeholder="ns1.example.com"
                                    className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                                />
                                {data.nameservers.length > 1 && (
                                    <button
                                        type="button"
                                        onClick={() => removeNs(i)}
                                        className="text-gray-400 hover:text-red-500 transition-colors p-1"
                                    >
                                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                )}
                            </div>
                        ))}
                        {errors.nameservers && (
                            <p className="text-xs text-red-600">{errors.nameservers}</p>
                        )}
                    </div>

                    {data.nameservers.length < 5 && (
                        <button
                            type="button"
                            onClick={addNs}
                            className="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                        >
                            + {t('addNameserver')}
                        </button>
                    )}

                    <div className="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            onClick={() => setShowNsModal(false)}
                            className="px-4 py-2 text-sm font-medium text-gray-700 border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors"
                        >
                            {t('cancel')}
                        </button>
                        <button
                            type="submit"
                            disabled={processing}
                            className="px-4 py-2 text-sm font-semibold text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 disabled:opacity-50 transition-colors"
                        >
                            {processing ? '…' : t('save')}
                        </button>
                    </div>
                </form>
            </Modal>
        </PortalLayout>
    );
}
