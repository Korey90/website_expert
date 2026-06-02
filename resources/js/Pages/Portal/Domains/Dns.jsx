import Modal from '@/Components/Modal';
import PortalLayout from '@/Layouts/PortalLayout';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

const T = {
    backToDomain:  { en: 'Back to Domain',          pl: 'Wróć do domeny',            pt: 'Voltar ao domínio' },
    dnsRecords:    { en: 'DNS Records',              pl: 'Rekordy DNS',               pt: 'Registos DNS' },
    addRecord:     { en: 'Add Record',               pl: 'Dodaj rekord',              pt: 'Adicionar registo' },
    noRecords:     { en: 'No DNS records found.',    pl: 'Brak rekordów DNS.',        pt: 'Nenhum registo DNS encontrado.' },
    type:          { en: 'Type',                     pl: 'Typ',                       pt: 'Tipo' },
    name:          { en: 'Name',                     pl: 'Nazwa',                     pt: 'Nome' },
    value:         { en: 'Value',                    pl: 'Wartość',                   pt: 'Valor' },
    ttl:           { en: 'TTL',                      pl: 'TTL',                       pt: 'TTL' },
    prio:          { en: 'Priority',                 pl: 'Priorytet',                 pt: 'Prioridade' },
    actions:       { en: 'Actions',                  pl: 'Akcje',                     pt: 'Ações' },
    edit:          { en: 'Edit',                     pl: 'Edytuj',                    pt: 'Editar' },
    delete:        { en: 'Delete',                   pl: 'Usuń',                      pt: 'Eliminar' },
    save:          { en: 'Save',                     pl: 'Zapisz',                    pt: 'Guardar' },
    cancel:        { en: 'Cancel',                   pl: 'Anuluj',                    pt: 'Cancelar' },
    confirmDelete: { en: 'Delete this record?',      pl: 'Usunąć ten rekord?',        pt: 'Eliminar este registo?' },
    yes:           { en: 'Yes, delete',              pl: 'Tak, usuń',                 pt: 'Sim, eliminar' },
    nsNote:        { en: 'DNS records are managed through Openprovider nameservers. If you use external nameservers, manage DNS records there.',
                     pl: 'Rekordy DNS są zarządzane przez serwery nazw Openprovider. Jeśli używasz własnych serwerów nazw, zarządzaj rekordami DNS tam.',
                     pt: 'Os registos DNS são geridos através dos servidores de nome Openprovider. Se usar servidores externos, gira os registos DNS nesse local.' },
};

const TYPE_COLORS = {
    A:     'bg-blue-100 text-blue-800',
    AAAA:  'bg-indigo-100 text-indigo-800',
    CNAME: 'bg-purple-100 text-purple-800',
    MX:    'bg-orange-100 text-orange-800',
    TXT:   'bg-gray-100 text-gray-700',
    NS:    'bg-green-100 text-green-800',
    SRV:   'bg-pink-100 text-pink-800',
};

const DNS_TYPES = ['A', 'AAAA', 'CNAME', 'MX', 'TXT', 'NS', 'SRV'];
const TTL_OPTIONS = [
    { label: '5 min (300)',      value: 300 },
    { label: '1 hour (3600)',    value: 3600 },
    { label: '4 hours (14400)', value: 14400 },
    { label: '24 hours (86400)', value: 86400 },
];

const EMPTY_RECORD = { type: 'A', name: '', value: '', ttl: 3600, prio: 0 };

export default function DomainsDns({ client, domain, records }) {
    const { locale, flash } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? key;

    const [showModal, setShowModal]         = useState(false);
    const [editingRecord, setEditingRecord] = useState(null);
    const [deleteTarget, setDeleteTarget]   = useState(null);

    const { data, setData, post, put, delete: destroy, processing, errors, reset } = useForm(EMPTY_RECORD);

    function openAdd() {
        reset();
        setData({ ...EMPTY_RECORD });
        setEditingRecord(null);
        setShowModal(true);
    }

    function openEdit(record) {
        setData({
            type:  record.type,
            name:  record.name,
            value: record.value,
            ttl:   record.ttl,
            prio:  record.prio,
        });
        setEditingRecord(record);
        setShowModal(true);
    }

    function submitRecord(e) {
        e.preventDefault();
        if (editingRecord) {
            put(route('portal.domains.dns.update', [domain.id, editingRecord.id]), {
                onSuccess: () => setShowModal(false),
            });
        } else {
            post(route('portal.domains.dns.store', domain.id), {
                onSuccess: () => setShowModal(false),
            });
        }
    }

    function confirmDelete(record) {
        setDeleteTarget(record);
    }

    function doDelete() {
        if (!deleteTarget) return;
        destroy(route('portal.domains.dns.destroy', [domain.id, deleteTarget.id]), {
            onSuccess: () => setDeleteTarget(null),
        });
    }

    const showPrio = data.type === 'MX' || data.type === 'SRV';

    return (
        <PortalLayout client={client}>
            <Head title={`DNS — ${domain.full_domain}`} />
            <div className="max-w-5xl mx-auto space-y-6">

                {/* Flash */}
                {flash?.success && (
                    <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-800">
                        {flash.success}
                    </div>
                )}

                {/* Header */}
                <div>
                    <Link
                        href={route('portal.domains.show', domain.id)}
                        className="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-gray-700 transition-colors mb-3"
                    >
                        <svg className="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="2">
                            <path strokeLinecap="round" strokeLinejoin="round" d="M15 19l-7-7 7-7" />
                        </svg>
                        {t('backToDomain')}
                    </Link>
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">{domain.full_domain}</h1>
                            <p className="text-sm text-gray-500 mt-0.5">{t('dnsRecords')}</p>
                        </div>
                        <button
                            onClick={openAdd}
                            className="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-lg hover:bg-indigo-700 active:scale-95 transition-all"
                        >
                            + {t('addRecord')}
                        </button>
                    </div>
                </div>

                {/* NS note */}
                <div className="rounded-lg bg-blue-50 border border-blue-200 px-4 py-3 text-sm text-blue-800">
                    {t('nsNote')}
                </div>

                {/* Records table */}
                <div className="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                    {records.length === 0 ? (
                        <div className="px-6 py-10 text-center text-sm text-gray-400">{t('noRecords')}</div>
                    ) : (
                        <table className="min-w-full text-sm">
                            <thead className="bg-gray-50 border-b border-gray-200">
                                <tr>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('type')}</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('name')}</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('value')}</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('ttl')}</th>
                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{t('prio')}</th>
                                    <th className="px-4 py-3"></th>
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {records.map(record => (
                                    <tr key={record.id} className="hover:bg-gray-50 transition-colors">
                                        <td className="px-4 py-3">
                                            <span className={`inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold ${TYPE_COLORS[record.type] ?? 'bg-gray-100 text-gray-700'}`}>
                                                {record.type}
                                            </span>
                                        </td>
                                        <td className="px-4 py-3 font-mono text-gray-800">{record.name || '@'}</td>
                                        <td className="px-4 py-3 font-mono text-gray-700 max-w-xs truncate">{record.value}</td>
                                        <td className="px-4 py-3 text-gray-500">{record.ttl}</td>
                                        <td className="px-4 py-3 text-gray-500">{record.prio || '—'}</td>
                                        <td className="px-4 py-3">
                                            {deleteTarget?.id === record.id ? (
                                                <span className="inline-flex items-center gap-2">
                                                    <button onClick={doDelete} disabled={processing} className="text-xs text-red-600 hover:text-red-800 font-medium disabled:opacity-50">
                                                        {t('yes')}
                                                    </button>
                                                    <button onClick={() => setDeleteTarget(null)} className="text-xs text-gray-500 hover:text-gray-700">
                                                        {t('cancel')}
                                                    </button>
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-3">
                                                    <button onClick={() => openEdit(record)} className="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                                        {t('edit')}
                                                    </button>
                                                    <button onClick={() => confirmDelete(record)} className="text-xs text-red-500 hover:text-red-700 font-medium">
                                                        {t('delete')}
                                                    </button>
                                                </span>
                                            )}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>
            </div>

            {/* Add / Edit modal */}
            <Modal show={showModal} maxWidth="lg" onClose={() => setShowModal(false)}>
                <form onSubmit={submitRecord} className="p-6 space-y-4">
                    <h3 className="text-base font-semibold text-gray-900">
                        {editingRecord ? t('edit') : t('addRecord')}
                    </h3>

                    <div className="grid grid-cols-2 gap-4">
                        <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">{t('type')}</label>
                            <select
                                value={data.type}
                                onChange={e => setData('type', e.target.value)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                {DNS_TYPES.map(type => (
                                    <option key={type} value={type}>{type}</option>
                                ))}
                            </select>
                        </div>
                        <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">{t('ttl')}</label>
                            <select
                                value={data.ttl}
                                onChange={e => setData('ttl', parseInt(e.target.value))}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            >
                                {TTL_OPTIONS.map(o => (
                                    <option key={o.value} value={o.value}>{o.label}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">{t('name')}</label>
                        <input
                            type="text"
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                            placeholder="@ or subdomain"
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.name && <p className="mt-1 text-xs text-red-600">{errors.name}</p>}
                    </div>

                    <div>
                        <label className="block text-xs font-medium text-gray-700 mb-1">{t('value')}</label>
                        <input
                            type="text"
                            value={data.value}
                            onChange={e => setData('value', e.target.value)}
                            placeholder={data.type === 'A' ? '1.2.3.4' : data.type === 'MX' ? 'mail.example.com' : ''}
                            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        />
                        {errors.value && <p className="mt-1 text-xs text-red-600">{errors.value}</p>}
                    </div>

                    {showPrio && (
                        <div>
                            <label className="block text-xs font-medium text-gray-700 mb-1">{t('prio')}</label>
                            <input
                                type="number"
                                min="0"
                                max="65535"
                                value={data.prio}
                                onChange={e => setData('prio', parseInt(e.target.value) || 0)}
                                className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            />
                        </div>
                    )}

                    <div className="flex justify-end gap-3 pt-2">
                        <button
                            type="button"
                            onClick={() => setShowModal(false)}
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
