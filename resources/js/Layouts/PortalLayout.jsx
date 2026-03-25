import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function PortalLayout({ client, children }) {
    const { url } = usePage();
    const [mobileOpen, setMobileOpen] = useState(false);

    const nav = [
        { href: route('portal.dashboard'), label: 'Dashboard', icon: '🏠' },
        { href: route('portal.projects'), label: 'Projects', icon: '📁' },
        { href: route('portal.contracts'), label: 'Contracts', icon: '📝' },
        { href: route('portal.invoices'), label: 'Invoices', icon: '🧾' },
        { href: route('portal.quotes'), label: 'Quotes', icon: '📋' },
    ];

    const isActive = (href) => url.startsWith(new URL(href).pathname);

    return (
        <div className="min-h-screen bg-gray-50 flex">
            {/* Sidebar */}
            <aside className={`fixed inset-y-0 left-0 z-40 w-64 bg-white shadow-lg transform transition-transform duration-200 ease-in-out
                ${mobileOpen ? 'translate-x-0' : '-translate-x-full'} lg:translate-x-0 lg:static lg:inset-0`}>
                <div className="flex flex-col h-full">
                    <div className="flex items-center px-6 py-5 border-b border-gray-200">
                        <div>
                            <div className="text-lg font-bold text-red-600">WebsiteExpert</div>
                            <div className="text-xs text-gray-500">Client Portal</div>
                        </div>
                    </div>

                    {client && (
                        <div className="px-6 py-4 bg-gray-50 border-b border-gray-200">
                            <div className="text-xs text-gray-500 uppercase tracking-wide">Logged in as</div>
                            <div className="text-sm font-semibold text-gray-800 mt-1 truncate">{client.company_name}</div>
                        </div>
                    )}

                    <nav className="flex-1 px-4 py-6 space-y-1 overflow-y-auto">
                        {nav.map((item) => (
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
                    </nav>

                    <div className="px-4 py-4 border-t border-gray-200">
                        <Link
                            href={route('portal.settings.notifications')}
                            className={`flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                                ${isActive(route('portal.settings.notifications'))
                                    ? 'bg-red-50 text-red-700 font-medium'
                                    : 'text-gray-600 hover:bg-gray-100'}`}
                        >
                            <span>🔔</span> Notifications
                        </Link>
                        <Link
                            href={route('profile.edit')}
                            className="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 mt-1"
                        >
                            <span>⚙️</span> Account Settings
                        </Link>
                        <Link
                            href={route('logout')}
                            method="post"
                            as="button"
                            className="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm text-gray-600 hover:bg-gray-100 mt-1"
                        >
                            <span>🚪</span> Log Out
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
            <div className="flex-1 flex flex-col min-w-0">
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
