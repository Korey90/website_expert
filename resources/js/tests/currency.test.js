import { describe, expect, it } from 'vitest';
import { servicePriceLabel } from '@/utils/servicePrice';
import { formatCurrency, normalizeCurrency } from '@/utils/currency';

const currencies = {
    GBP: {
        code: 'GBP',
        symbol: '£',
        display_locale: 'en-GB',
        decimal_digits: 2,
        symbol_position: 'before',
    },
    PLN: {
        code: 'PLN',
        symbol: 'zł',
        display_locale: 'pl-PL',
        decimal_digits: 2,
        symbol_position: 'after',
    },
};

describe('currency utils', () => {
    it('normalizes supported currencies', () => {
        expect(normalizeCurrency('pln', currencies)).toBe('PLN');
    });

    it('falls back for unsupported currencies', () => {
        expect(normalizeCurrency('AUD', currencies)).toBe('GBP');
    });

    it('formats currency values with Intl', () => {
        expect(formatCurrency(10, 'GBP', 'en-GB', { currencies })).toBe('£10.00');
    });

    it('formats service price books with the selected currency', () => {
        const label = servicePriceLabel(
            {
                price_from_prices: {
                    GBP: 499,
                    PLN: 2495,
                },
                price_from_period: 'monthly',
            },
            'pl',
            (amount, currency) => `${amount} ${currency}`,
            'PLN',
        );

        expect(label).toBe('2495 PLN/mc');
    });
});
