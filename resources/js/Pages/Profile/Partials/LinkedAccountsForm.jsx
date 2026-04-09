import { router, usePage } from '@inertiajs/react';
import { useState } from 'react';

const T = {
    title:       { en: 'Connected Accounts', pl: 'Połączone konta',        pt: 'Contas conectadas' },
    subtitle:    { en: 'Manage your social login connections.',
                   pl: 'Zarządzaj połączeniami z kontami społecznościowymi.',
                   pt: 'Gerencie suas conexões de login social.' },
    connected:   { en: 'Connected',          pl: 'Połączone',              pt: 'Conectado' },
    not_linked:  { en: 'Not connected',      pl: 'Niepołączone',           pt: 'Não conectado' },
    disconnect:  { en: 'Disconnect',         pl: 'Odłącz',                 pt: 'Desconectar' },
    connect:     { en: 'Connect',            pl: 'Połącz',                 pt: 'Conectar' },
    last_method: { en: 'Set a password first to disconnect this account.',
                   pl: 'Najpierw ustaw hasło, aby odłączyć to konto.',
                   pt: 'Defina uma senha primeiro para desconectar esta conta.' },
    unlinked:    { en: ' account disconnected.',
                   pl: ' odłączone pomyślnie.',
                   pt: ' desconectada com sucesso.' },
    linked_ok:   { en: 'Account connected successfully.',
                   pl: 'Konto zostało pomyślnie połączone.',
                   pt: 'Conta conectada com sucesso.' },
    already_linked: { en: 'This account is already connected.',
                      pl: 'To konto jest już połączone.',
                      pt: 'Esta conta já está conectada.' },
    confirm:     { en: 'Disconnect :provider?',
                   pl: 'Odłączyć :provider?',
                   pt: 'Desconectar :provider?' },
};

const PROVIDERS = [
    {
        key: 'google',
        label: 'Google',
        icon: (
            <svg viewBox="0 0 24 24" className="w-5 h-5" aria-hidden="true">
                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l3.66-2.84z" fill="#FBBC05"/>
                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
            </svg>
        ),
    },
    {
        key: 'facebook',
        label: 'Facebook',
        icon: (
            <svg viewBox="0 0 24 24" className="w-5 h-5" fill="#1877F2" aria-hidden="true">
                <path d="M24 12.073C24 5.405 18.627 0 12 0S0 5.405 0 12.073C0 18.1 4.388 23.094 10.125 24v-8.437H7.078v-3.49h3.047V9.41c0-3.025 1.792-4.697 4.533-4.697 1.312 0 2.686.235 2.686.235v2.97h-1.513c-1.491 0-1.956.93-1.956 1.886v2.271h3.328l-.532 3.49h-2.796V24C19.612 23.094 24 18.1 24 12.073z"/>
            </svg>
        ),
    },
];

export default function LinkedAccountsForm({ socialAccounts = [], hasPassword }) {
    const { locale, errors } = usePage().props;
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? '';

    const [processing, setProcessing] = useState(null);

    const linkedProviders = new Set(socialAccounts.map((sa) => sa.provider));
    const canDisconnect   = hasPassword || linkedProviders.size > 1;

    const handleDisconnect = (providerKey, providerLabel) => {
        if (! confirm(t('confirm').replace(':provider', providerLabel))) return;

        setProcessing(providerKey);

        router.delete(route('profile.social.unlink', { provider: providerKey }), {
            preserveScroll: true,
            onFinish: () => setProcessing(null),
        });
    };

    const socialError = errors?.social;
    const status      = usePage().props.status;

    return (
        <section className="space-y-6">
            <header>
                <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                    {t('title')}
                </h2>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    {t('subtitle')}
                </p>
            </header>

            {status === 'social-unlinked' && (
                <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    ✓ {t('unlinked')}
                </div>
            )}

            {status === 'social-linked' && (
                <div className="rounded-lg bg-green-50 border border-green-200 px-4 py-3 text-sm text-green-700">
                    ✓ {t('linked_ok')}
                </div>
            )}

            {status === 'social-already-linked' && (
                <div className="rounded-lg bg-yellow-50 border border-yellow-200 px-4 py-3 text-sm text-yellow-700">
                    {t('already_linked')}
                </div>
            )}

            {socialError && (
                <div className="rounded-lg bg-red-50 border border-red-200 px-4 py-3 text-sm text-red-700">
                    {socialError}
                </div>
            )}

            <ul className="divide-y divide-gray-100 dark:divide-gray-700">
                {PROVIDERS.map(({ key, label, icon }) => {
                    const isLinked = linkedProviders.has(key);
                    const isBusy   = processing === key;
                    const isOnly   = isLinked && !canDisconnect;

                    return (
                        <li key={key} className="flex items-center justify-between py-4">
                            <div className="flex items-center gap-3">
                                {icon}
                                <div>
                                    <p className="text-sm font-medium text-gray-900 dark:text-gray-100">
                                        {label}
                                    </p>
                                    <p className={`text-xs ${isLinked ? 'text-green-600' : 'text-gray-400'}`}>
                                        {isLinked ? t('connected') : t('not_linked')}
                                    </p>
                                </div>
                            </div>

                            {isLinked ? (
                                <div className="flex flex-col items-end gap-1">
                                    <button
                                        type="button"
                                        disabled={isBusy || isOnly}
                                        onClick={() => handleDisconnect(key, label)}
                                        title={isOnly ? t('last_method') : undefined}
                                        className={`text-xs px-3 py-1.5 rounded-lg border transition-colors
                                            ${isOnly
                                                ? 'border-gray-200 text-gray-400 cursor-not-allowed'
                                                : 'border-red-200 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20'}`}
                                    >
                                        {isBusy ? '…' : t('disconnect')}
                                    </button>
                                    {isOnly && (
                                        <p className="text-xs text-gray-400 text-right max-w-[180px]">
                                            {t('last_method')}
                                        </p>
                                    )}
                                </div>
                            ) : (
                                <a
                                    href={route('profile.social.connect', { provider: key })}
                                    className="text-xs px-3 py-1.5 rounded-lg border border-gray-300 text-gray-600
                                               hover:border-gray-400 hover:text-gray-800 transition-colors
                                               dark:border-gray-600 dark:text-gray-300"
                                >
                                    {t('connect')}
                                </a>
                            )}
                        </li>
                    );
                })}
            </ul>
        </section>
    );
}
