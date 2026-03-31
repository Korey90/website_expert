import { describe, it, expect } from 'vitest';
import { renderHook } from '@testing-library/react';
import { vi } from 'vitest';

// Mock @inertiajs/react before importing hook
vi.mock('@inertiajs/react', () => ({
    usePage: () => ({
        props: {
            portal_translations: {
                welcome_back: 'Welcome back, :name',
                no_projects:  'No projects yet.',
                view_all:     'View all →',
            },
        },
    }),
}));

import usePortalTrans from '@/hooks/usePortalTrans';

describe('usePortalTrans', () => {
    it('returns a translation function', () => {
        const { result } = renderHook(() => usePortalTrans());
        expect(typeof result.current).toBe('function');
    });

    it('translates a known key', () => {
        const { result } = renderHook(() => usePortalTrans());
        expect(result.current('no_projects')).toBe('No projects yet.');
    });

    it('returns the key when translation is missing', () => {
        const { result } = renderHook(() => usePortalTrans());
        expect(result.current('unknown_key')).toBe('unknown_key');
    });

    it('replaces :name placeholder', () => {
        const { result } = renderHook(() => usePortalTrans());
        expect(result.current('welcome_back', { name: 'Alice' })).toBe('Welcome back, Alice');
    });

    it('handles multiple calls consistently', () => {
        const { result } = renderHook(() => usePortalTrans());
        const t = result.current;
        expect(t('view_all')).toBe('View all →');
        expect(t('view_all')).toBe('View all →');
    });
});
