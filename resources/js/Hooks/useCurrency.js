import { usePage } from '@inertiajs/react';
import { formatCurrency as formatCurrencyValue, getCurrencyMeta } from '@/utils/currency';

export default function useCurrency() {
    const {
        locale = 'en',
        currency = 'GBP',
        available_currencies: availableCurrencies = {},
        currency_settings: currencySettings = {},
    } = usePage().props;

    const meta = Object.keys(currencySettings).length > 0
        ? currencySettings
        : getCurrencyMeta(currency, availableCurrencies);

    return {
        currency,
        locale,
        meta,
        availableCurrencies,
        formatCurrency: (amount, overrideCurrency = currency, options = {}) => formatCurrencyValue(
            amount,
            overrideCurrency,
            options.locale ?? locale,
            {
                ...options,
                currencies: options.currencies ?? availableCurrencies,
            },
        ),
    };
}
