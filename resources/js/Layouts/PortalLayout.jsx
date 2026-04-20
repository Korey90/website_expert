import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

const T = {
    portal:           { en: 'Portal',           pl: 'Portal',           pt: 'Portal' },
    dashboard:        { en: 'Dashboard',         pl: 'Pulpit',           pt: 'Painel' },
    projects:         { en: 'Projects',          pl: 'Projekty',         pt: 'Projetos' },
    contracts:        { en: 'Contracts',         pl: 'Umowy',            pt: 'Contratos' },
    invoices:         { en: 'Invoices',          pl: 'Faktury',          pt: 'Faturas' },
    quotes:           { en: 'Quotes',            pl: 'Wyceny',           pt: 'Orçamentos' },
    growthTools:      { en: 'Growth Tools',      pl: 'Narzędzia wzrostu',pt: 'Ferramentas' },
    landingPages:     { en: 'Landing Pages',     pl: 'Landing Pages',    pt: 'Landing Pages' },
    aiGenerator:      { en: 'AI Generator',      pl: 'Generator AI',     pt: 'Gerador AI' },
    business:         { en: 'Business',          pl: 'Firma',            pt: 'Empresa' },
    bizProfile:       { en: 'Brand Profile',     pl: 'Profil marki',     pt: 'Perfil da marca' },
    bizSettings:      { en: 'Settings',          pl: 'Ustawienia',       pt: 'Configurações' },
    apiTokens:        { en: 'API Tokens',        pl: 'Tokeny API',       pt: 'Tokens de API' },
    billing:          { en: 'Billing & Plan',    pl: 'Billing i Plan',   pt: 'Faturação e Plano' },
    notifications:    { en: 'Notifications',     pl: 'Powiadomienia',    pt: 'Notificações' },
    accountSettings:  { en: 'Account Settings',  pl: 'Ustawienia konta', pt: 'Configurações de conta' },
    logOut:           { en: 'Log Out',           pl: 'Wyloguj się',      pt: 'Sair' },
    loggedAs:         { en: 'Logged in as',      pl: 'Zalogowany jako',  pt: 'Conectado como' },
    clientPortal:     { en: 'Client Portal',     pl: 'Portal klienta',   pt: 'Portal do cliente' },
};

export default function PortalLayout({ client, children }) {
    const { url, props } = usePage();
    const locale = props.locale ?? 'en';
    const t = (key) => T[key]?.[locale] ?? T[key]?.en ?? key;
    const [mobileOpen, setMobileOpen] = useState(false);
    const capabilities = props.auth?.portal_capabilities ?? {};
    const canAccessClientPortal = !!capabilities.can_access_client_portal;
    const canAccessWorkspace = !!capabilities.can_access_workspace;

    const sections = [
        canAccessClientPortal ? {
            key: 'portal',
            label: t('portal'),
            items: [
                { href: route('portal.dashboard'), label: t('dashboard'),  icon: '🏠' },
                { href: route('portal.projects'),  label: t('projects'),   icon: '📁' },
                { href: route('portal.contracts'), label: t('contracts'),  icon: '📝' },
                { href: route('portal.invoices'),  label: t('invoices'),   icon: '🧾' },
                { href: route('portal.quotes'),    label: t('quotes'),     icon: '📋' },
            ],
        } : null,
        canAccessWorkspace ? {
            key: 'growthTools',
            label: t('growthTools'),
            items: [
                { href: route('landing-pages.index'),     label: t('landingPages'), icon: '🚀' },
                { href: route('landing-pages.ai.create'), label: t('aiGenerator'),  icon: '✨' },
            ],
        } : null,
        canAccessWorkspace ? {
            key: 'business',
            label: t('business'),
            items: [
                { href: route('business.profile.edit'),    label: t('bizProfile'),  icon: '🎨' },
                { href: route('business.edit'),            label: t('bizSettings'), icon: '🏢' },
                { href: route('business.api-tokens.index'),label: t('apiTokens'),   icon: '🔑' },
                { href: route('portal.billing'),            label: t('billing'),     icon: '💳' },
            ],
        } : null,
    ].filter(Boolean);

    const isActive = (href) => url.startsWith(new URL(href).pathname);

    return (
        <div className="h-screen bg-gray-50 flex overflow-hidden">
            {/* Sidebar */}
            <aside className={`flex-shrink-0 w-64 bg-white shadow-lg flex flex-col
                fixed inset-y-0 left-0 z-40 transform transition-transform duration-200 ease-in-out
                ${mobileOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0 lg:static lg:inset-auto`}>
                <div className="flex flex-col h-full overflow-hidden">
                    <div className="flex items-center px-6 py-5 border-b border-gray-200">
                        <div>
                            <div className="text-lg font-bold text-red-600">WebsiteExpert</div>
                            <div className="text-xs text-gray-500">{t('clientPortal')}</div>
                        </div>
                    </div>

                    {client && (
                        <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div className="text-xs text-gray-500 uppercase tracking-wide">{t('loggedAs')}</div>
                            <div className="text-sm font-semibold text-gray-800 mt-1 truncate">{client.company_name}</div>
                        </div>
                    )}

                    <nav className="flex-1 px-4 py-4 overflow-y-auto space-y-4">
                        {sections.map((section) => (
                            <div key={section.key}>
                                <div className="px-3 mb-1 text-xs font-semibold text-gray-400 uppercase tracking-wider">
                                    {section.label}
                                </div>
                                {section.items.map((item) => (
                                    <Link
                                        key={item.href}
                                        href={item.href}
                                        className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors
                                            ${isActive(item.href)
                                                ? 'bg-red-50 text-red-700'
                                                : 'text-gray-600 hover:bg-gray-100 hover:text-gray-900'}`}
                                    >
                                        <span className="text-base">{item.icon}</span>
                                        {item.label}
                                    </Link>
                                ))}
                            </div>
                        ))}
                    </nav>

                    <div className="px-4 py-4 border-t border-gray-200 space-y-1">
                        {canAccessClientPortal && (
                            <Link
                                href={route('portal.settings.notifications')}
                                className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                                    ${isActive(route('portal.settings.notifications'))
                                        ? 'bg-red-50 text-red-700 font-medium'
                                        : 'text-gray-600 hover:bg-gray-100'}`}
                            >
                                <span>🔔</span> {t('notifications')}
                            </Link>
                        )}
                        <Link
                            href={route('profile.edit')}
                            className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                                ${isActive(route('profile.edit'))
                                    ? 'bg-red-50 text-red-700 font-medium'
                                    : 'text-gray-600 hover:bg-gray-100'}`}
                        >
                            <span>⚙️</span> {t('accountSettings')}
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100"
                        >
                            <span>🚪</span> {t('logOut')}
                        </Link>
                    </div>
                </div>
            </aside>

            {/* Mobile overlay */}
            {mobileOpen && (
                <div
                    className="fixed inset-0 z-30 bg-black bg-opacity-40 lg:hidden"
                    onClick={() => setMobileOpen(false)}
                />
            )}

            {/* Main content */}
            <div className="flex-1 flex flex-col min-w-0 overflow-hidden">
                {/* Mobile top bar */}
                <header className="lg:hidden flex items-center px-4 py-3 bg-white border-b border-gray-200 shadow-sm">
                    <button
                        onClick={() => setMobileOpen(true)}
                        className="p-2 rounded-md text-gray-500 hover:bg-gray-100"
                    >
                        <svg className="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 6h16M4 12h16M4 18h16" />
                        </svg>
                    </button>
                    <span className="ml-3 text-red-600 font-bold">WebsiteExpert Portal</span>
                </header>

                <main className="flex-1 p-6 overflow-auto">
                    {children}
                </main>
            </div>
        </div>
    );
}
