import { useEffect, useState } from 'react';

export default function useThemeMode(defaultTheme = 'light') {
    const [theme, setTheme] = useState(() => {
        if (typeof window === 'undefined') {
            return defaultTheme;
        }

        const stored = window.localStorage.getItem('theme');

        if (stored === 'dark' || stored === 'light') {
            return stored;
        }

        return defaultTheme;
    });

    useEffect(() => {
        document.documentElement.classList.toggle('dark', theme === 'dark');
        window.localStorage.setItem('theme', theme);
    }, [theme]);

    return {
        theme,
        isDark: theme === 'dark',
        setTheme,
        toggleTheme: () => setTheme((current) => current === 'dark' ? 'light' : 'dark'),
    };
}