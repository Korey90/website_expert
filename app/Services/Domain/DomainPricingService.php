<?php

namespace App\Services\Domain;

use App\Data\Domain\DomainPriceSnapshot;
use App\Models\DomainPriceList;
use App\Services\Currency\CurrencyResolver;
use Illuminate\Support\Collection;

class DomainPricingService
{
    private const DEFAULT_TLDS = ['.co.uk', '.uk', '.com', '.net', '.org'];

    public function __construct(private readonly CurrencyResolver $currencyResolver) {}

    /**
     * Get price snapshot for a single TLD.
     * Returns null if the TLD is not in the price list or is inactive.
     */
    public function getPriceForTld(string $tld, ?string $currency = null): ?DomainPriceSnapshot
    {
        $price = $this->findPriceList($tld, $currency);

        if ($price === null) {
            return null;
        }

        return DomainPriceSnapshot::fromPriceList(
            tld: $price->tld,
            registerPrice: (float) $price->register_price,
            renewPrice: (float) $price->renew_price,
            transferPrice: $price->transfer_price !== null ? (float) $price->transfer_price : null,
            currency: $price->currency,
        );
    }

    /**
     * Calculate retail price for an order.
     * Years multiplier is applied to the registration price.
     */
    public function calculateRetailPrice(string $tld, int $years, string $action = 'register', ?string $currency = null): ?float
    {
        $price = $this->findPriceList($tld, $currency);

        if ($price === null) {
            return null;
        }

        $unitPrice = match ($action) {
            'transfer' => (float) ($price->transfer_price ?? $price->register_price),
            'renew' => (float) $price->renew_price,
            default => (float) $price->register_price,
        };

        return round($unitPrice * $years, 2);
    }

    /**
     * Get prices for all active TLDs (for the pricing table on the public page).
     *
     * @return Collection<int, DomainPriceSnapshot>
     */
    public function getAllActivePrices(?string $currency = null): Collection
    {
        $targetCurrency = $this->resolveCurrency($currency);
        $defaultCurrency = $this->currencyResolver->defaultCurrency();
        $preferredOrder = array_flip(self::DEFAULT_TLDS);

        return DomainPriceList::active()
            ->get()
            ->groupBy('tld')
            ->map(fn (Collection $prices) => $this->choosePriceList($prices, $targetCurrency, $defaultCurrency))
            ->filter()
            ->sortBy(fn (DomainPriceList $item) => sprintf('%02d:%s', $preferredOrder[$item->tld] ?? 99, $item->tld))
            ->values()
            ->map(fn (DomainPriceList $item) => $this->snapshot($item));
    }

    /**
     * Returns the default TLDs we promote on the public page.
     *
     * @return string[]
     */
    public function getDefaultTlds(): array
    {
        return self::DEFAULT_TLDS;
    }

    public function resolveCurrency(?string $currency = null): string
    {
        if ($currency !== null) {
            return $this->currencyResolver->normalize($currency);
        }

        return $this->currencyResolver->resolve(request());
    }

    private function findPriceList(string $tld, ?string $currency = null): ?DomainPriceList
    {
        $tld = strtolower(trim($tld));
        $targetCurrency = $this->resolveCurrency($currency);
        $defaultCurrency = $this->currencyResolver->defaultCurrency();

        return DomainPriceList::forTld($tld, $targetCurrency)
            ?? ($targetCurrency !== $defaultCurrency ? DomainPriceList::forTld($tld, $defaultCurrency) : null)
            ?? DomainPriceList::forTld($tld);
    }

    /**
     * @param  Collection<int, DomainPriceList>  $prices
     */
    private function choosePriceList(Collection $prices, string $targetCurrency, string $defaultCurrency): ?DomainPriceList
    {
        return $prices->firstWhere('currency', $targetCurrency)
            ?? $prices->firstWhere('currency', $defaultCurrency)
            ?? $prices->sortBy('currency')->first();
    }

    private function snapshot(DomainPriceList $price): DomainPriceSnapshot
    {
        return DomainPriceSnapshot::fromPriceList(
            tld: $price->tld,
            registerPrice: (float) $price->register_price,
            renewPrice: (float) $price->renew_price,
            transferPrice: $price->transfer_price !== null ? (float) $price->transfer_price : null,
            currency: $price->currency,
        );
    }
}
