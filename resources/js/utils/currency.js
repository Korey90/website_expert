export function normalizeCurrency(currency, currencies = {}, fallback = 'GBP') {
    const normalized = typeof currency === 'string' ? currency.toUpperCase() : fallback;
    return currencies?.[normalized] ? normalized : fallback;
}

export function getCurrencyMeta(currency, currencies = {}) {
    const normalized = normalizeCurrency(currency, currencies);
    return currencies?.[normalized] ?? currencies?.GBP ?? {
        code: 'GBP',
        symbol: '£',
        display_locale: 'en-GB',
        decimal_digits: 2,
        symbol_position: 'before',
        decimal_separator: '.',
        thousands_separator: ',',
    };
}

export function formatCurrency(amount, currency = 'GBP', locale = null, options = {}) {
    const currencies = options.currencies ?? {};
    const meta = getCurrencyMeta(currency, currencies);
    const code = meta.code ?? normalizeCurrency(currency, currencies);
    const value = Number.isFinite(Number(amount)) ? Number(amount) : 0;
    const displayLocale = locale ?? meta.display_locale ?? 'en-GB';
    const defaultDigits = meta.decimal_digits ?? 2;
    const minDigits = Number.isInteger(options.minimumFractionDigits)
        ? options.minimumFractionDigits
        : defaultDigits;
    const maxDigits = Number.isInteger(options.maximumFractionDigits)
        ? options.maximumFractionDigits
        : defaultDigits;

    try {
        return new Intl.NumberFormat(displayLocale, {
            style: 'currency',
            currency: code,
            minimumFractionDigits: minDigits,
            maximumFractionDigits: maxDigits,
        }).format(value);
    } catch {
        return fallbackFormat(value, meta, maxDigits);
    }
}

function fallbackFormat(value, meta, digits) {
    const fixed = value.toFixed(digits);
    const [whole, fraction] = fixed.split('.');
    const thousandsSeparator = meta.thousands_separator ?? ',';
    const decimalSeparator = meta.decimal_separator ?? '.';
    const number = whole.replace(/\B(?=(\d{3})+(?!\d))/g, thousandsSeparator)
        + (digits > 0 ? `${decimalSeparator}${fraction}` : '');
    const symbol = meta.symbol ?? meta.code ?? '';

    return meta.symbol_position === 'after' ? `${number} ${symbol}` : `${symbol}${number}`;
}
