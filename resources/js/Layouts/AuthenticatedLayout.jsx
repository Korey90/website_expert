import ApplicationLogo from '@/Components/ApplicationLogo';
import Dropdown from '@/Components/Dropdown';
import useThemeMode from '@/Hooks/useThemeMode';
import NavLink from '@/Components/NavLink';
import ResponsiveNavLink from '@/Components/ResponsiveNavLink';
import { Link, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const { auth, locale, available_locales: availableLocales } = usePage().props;
    const user = auth.user;
    const { isDark, toggleTheme } = useThemeMode('light');

    const [showingNavigationDropdown, setShowingNavigationDropdown] =
        useState(false);

    const localeOptions = Object.entries(availableLocales ?? {});

    return (
        <div className="min-h-screen bg-neutral-100 text-neutral-900 transition-colors dark:bg-neutral-950 dark:text-white">
            <nav className="border-b border-neutral-200 bg-white/90 backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/90">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <div className="flex h-16 justify-between">
                        <div className="flex">
                            <div className="flex shrink-0 items-center">
                                <Link href="/">
                                    <ApplicationLogo className="block h-9 w-auto fill-current text-neutral-800 dark:text-white" />
                                </Link>
                            </div>

                            <div className="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                                <NavLink
                                    href="/admin"
                                    active={false}
                                >
                                    Dashboard
                                </NavLink>
                            </div>
                        </div>

                        <div className="hidden sm:ms-6 sm:flex sm:items-center sm:gap-3">
                            <div className="flex items-center gap-2 rounded-full border border-neutral-200 bg-neutral-50 px-2 py-1 dark:border-neutral-700 dark:bg-neutral-800">
                                {localeOptions.map(([code, label]) => (
                                    <Link
                                        key={code}
                                        href={route('lang.switch', code)}
                                        className={[
                                            'rounded-full px-2.5 py-1 text-xs font-semibold transition',
                                            locale === code
                                                ? 'bg-white text-brand-600 shadow-sm dark:bg-neutral-700 dark:text-brand-300'
                                                : 'text-neutral-500 hover:text-neutral-900 dark:text-neutral-400 dark:hover:text-white',
                                        ].join(' ')}
                                    >
                                        {typeof label === 'string' ? label.replace(/\s*\p{Emoji_Presentation}+$/u, '').trim() : code.toUpperCase()}
                                    </Link>
                                ))}
                            </div>
                            <button
                                type="button"
                                onClick={toggleTheme}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-white text-neutral-500 transition hover:border-brand-400 hover:text-brand-600 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300 dark:hover:text-brand-300"
                                aria-label="Toggle theme"
                            >
                                {isDark ? (
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2.25M12 18.75V21M4.955 4.955l1.591 1.591M17.455 17.455l1.59 1.59M3 12h2.25M18.75 12H21M4.955 19.045l1.591-1.59M17.455 6.545l1.59-1.59M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0Z" />
                                    </svg>
                                ) : (
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3c-.025.248-.038.5-.038.75A9 9 0 0021 12.79Z" />
                                    </svg>
                                )}
                            </button>
                            <div className="relative ms-3">
                                <Dropdown>
                                    <Dropdown.Trigger>
                                        <span className="inline-flex rounded-md">
                                            <button
                                                type="button"
                                                className="inline-flex items-center rounded-md border border-transparent bg-transparent px-3 py-2 text-sm font-medium leading-4 text-neutral-500 transition duration-150 ease-in-out hover:text-neutral-800 focus:outline-none dark:text-neutral-300 dark:hover:text-white"
                                            >
                                                {user.name}

                                                <svg
                                                    className="-me-0.5 ms-2 h-4 w-4"
                                                    xmlns="http://www.w3.org/2000/svg"
                                                    viewBox="0 0 20 20"
                                                    fill="currentColor"
                                                >
                                                    <path
                                                        fillRule="evenodd"
                                                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                                        clipRule="evenodd"
                                                    />
                                                </svg>
                                            </button>
                                        </span>
                                    </Dropdown.Trigger>

                                    <Dropdown.Content>
                                        <Dropdown.Link
                                            href={route('profile.edit')}
                                        >
                                            Profile
                                        </Dropdown.Link>
                                        <Dropdown.Link
                                            href={route('logout')}
                                            method="post"
                                            as="button"
                                        >
                                            Log Out
                                        </Dropdown.Link>
                                    </Dropdown.Content>
                                </Dropdown>
                            </div>
                        </div>

                        <div className="-me-2 flex items-center gap-2 sm:hidden">
                            <button
                                type="button"
                                onClick={toggleTheme}
                                className="inline-flex h-10 w-10 items-center justify-center rounded-full border border-neutral-200 bg-white text-neutral-500 dark:border-neutral-700 dark:bg-neutral-800 dark:text-neutral-300"
                                aria-label="Toggle theme"
                            >
                                {isDark ? (
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M12 3v2.25M12 18.75V21M4.955 4.955l1.591 1.591M17.455 17.455l1.59 1.59M3 12h2.25M18.75 12H21M4.955 19.045l1.591-1.59M17.455 6.545l1.59-1.59M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0Z" />
                                    </svg>
                                ) : (
                                    <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth="1.8">
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M21 12.79A9 9 0 1111.21 3c-.025.248-.038.5-.038.75A9 9 0 0021 12.79Z" />
                                    </svg>
                                )}
                            </button>
                            <button
                                onClick={() =>
                                    setShowingNavigationDropdown(
                                        (previousState) => !previousState,
                                    )
                                }
                                className="inline-flex items-center justify-center rounded-md p-2 text-neutral-400 transition duration-150 ease-in-out hover:bg-neutral-100 hover:text-neutral-500 focus:bg-neutral-100 focus:text-neutral-500 focus:outline-none dark:hover:bg-neutral-800 dark:hover:text-neutral-300 dark:focus:bg-neutral-800 dark:focus:text-neutral-300"
                            >
                                <svg
                                    className="h-6 w-6"
                                    stroke="currentColor"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        className={
                                            !showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M4 6h16M4 12h16M4 18h16"
                                    />
                                    <path
                                        className={
                                            showingNavigationDropdown
                                                ? 'inline-flex'
                                                : 'hidden'
                                        }
                                        strokeLinecap="round"
                                        strokeLinejoin="round"
                                        strokeWidth="2"
                                        d="M6 18L18 6M6 6l12 12"
                                    />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <div
                    className={
                        (showingNavigationDropdown ? 'block' : 'hidden') +
                        ' sm:hidden'
                    }
                >
                    <div className="space-y-1 pb-3 pt-2">
                        <ResponsiveNavLink
                            href="/admin"
                            active={false}
                        >
                            Dashboard
                        </ResponsiveNavLink>
                    </div>

                    <div className="border-t border-gray-200 pb-1 pt-4">
                        <div className="flex flex-wrap gap-2 px-4 pb-3">
                            {localeOptions.map(([code, label]) => (
                                <Link
                                    key={code}
                                    href={route('lang.switch', code)}
                                    className={[
                                        'rounded-full border px-3 py-1 text-xs font-semibold transition',
                                        locale === code
                                            ? 'border-brand-400 bg-brand-50 text-brand-700 dark:bg-brand-900/20 dark:text-brand-300'
                                            : 'border-neutral-200 text-neutral-500 dark:border-neutral-700 dark:text-neutral-400',
                                    ].join(' ')}
                                >
                                    {typeof label === 'string' ? label.replace(/\s*\p{Emoji_Presentation}+$/u, '').trim() : code.toUpperCase()}
                                </Link>
                            ))}
                        </div>
                        <div className="px-4">
                            <div className="text-base font-medium text-gray-800 dark:text-white">
                                {user.name}
                            </div>
                            <div className="text-sm font-medium text-gray-500 dark:text-neutral-400">
                                {user.email}
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={route('profile.edit')}>
                                Profile
                            </ResponsiveNavLink>
                            <ResponsiveNavLink
                                method="post"
                                href={route('logout')}
                                as="button"
                            >
                                Log Out
                            </ResponsiveNavLink>
                        </div>
                    </div>
                </div>
            </nav>

            {header && (
                <header className="border-b border-neutral-200 bg-white/85 shadow-sm backdrop-blur dark:border-neutral-800 dark:bg-neutral-900/85">
                    <div className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                        {header}
                    </div>
                </header>
            )}

            <main>{children}</main>
        </div>
    );
}
