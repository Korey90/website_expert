const PERIOD_SUFFIXES = {
    monthly: {
        en: '/mo',
        pl: '/mc',
        pt: '/mês',
    },
    yearly: {
        en: '/yr',
        pl: '/rok',
        pt: '/ano',
    },
};

export function servicePriceLabel(service, locale = 'en', formatCurrency = null, selectedCurrency = null) {
    const { amount, currency } = resolveStructuredPrice(service, selectedCurrency);
    const hasStructuredPrice = amount !== null && amount !== undefined && currency;

    const formatted = hasStructuredPrice && typeof formatCurrency === 'function'
        ? formatCurrency(amount, currency, compactCurrencyOptions(amount))
        : service?.price_from_formatted ?? service?.price_from ?? '';

    if (!formatted) {
        return '';
    }

    return `${formatted}${periodSuffix(service?.price_from_period, locale)}`;
}

function resolveStructuredPrice(service, selectedCurrency) {
    const directAmount = service?.price_from_amount;
    const directCurrency = normaliseCurrency(service?.price_from_currency);

    if (directAmount !== null && directAmount !== undefined && directCurrency) {
        return {
            amount: directAmount,
            currency: directCurrency,
        };
    }

    const preferredCurrency = normaliseCurrency(selectedCurrency) ?? directCurrency ?? 'GBP';
    const preferredAmount = amountFromPriceBook(service?.price_from_prices, preferredCurrency);

    if (preferredAmount !== null) {
        return {
            amount: preferredAmount,
            currency: preferredCurrency,
        };
    }

    const fallbackAmount = preferredCurrency === 'GBP'
        ? null
        : amountFromPriceBook(service?.price_from_prices, 'GBP');

    return {
        amount: fallbackAmount,
        currency: fallbackAmount === null ? null : 'GBP',
    };
}

function amountFromPriceBook(priceBook, currency) {
    if (!priceBook || !currency) {
        return null;
    }

    if (!Array.isArray(priceBook) && typeof priceBook === 'object' && currency in priceBook) {
        return numericAmount(priceBook[currency]);
    }

    if (!Array.isArray(priceBook)) {
        return null;
    }

    const entry = priceBook.find(item => normaliseCurrency(item?.currency) === currency);

    return numericAmount(entry?.amount ?? entry?.price ?? entry?.value);
}

function numericAmount(value) {
    if (value && typeof value === 'object') {
        value = value.amount ?? value.price ?? value.value;
    }

    const amount = Number(value);

    return Number.isFinite(amount) ? amount : null;
}

function normaliseCurrency(currency) {
    if (typeof currency !== 'string' || currency.trim() === '') {
        return null;
    }

    return currency.trim().toUpperCase();
}

function periodSuffix(period, locale) {
    if (period === 'one_time' || !period) {
        return '';
    }

    return PERIOD_SUFFIXES[period]?.[locale] ?? PERIOD_SUFFIXES[period]?.en ?? '';
}

function compactCurrencyOptions(amount) {
    const value = Number(amount);
    const hasFraction = Number.isFinite(value) && Math.abs(value % 1) > 0.001;

    return {
        minimumFractionDigits: hasFraction ? 2 : 0,
        maximumFractionDigits: hasFraction ? 2 : 0,
    };
}
