<?php

namespace App\Services\Domain;

use App\Data\Domain\DomainPriceSnapshot;
use App\Models\DomainPriceList;
use Illuminate\Support\Collection;

class DomainPricingService
{
    private const DEFAULT_TLDS = ['.co.uk', '.uk', '.com', '.net', '.org'];

    /**
     * Get price snapshot for a single TLD.
     * Returns null if the TLD is not in the price list or is inactive.
     */
    public function getPriceForTld(string $tld): ?DomainPriceSnapshot
    {
        $price = DomainPriceList::forTld($tld);

        if ($price === null) {
            return null;
        }

        return DomainPriceSnapshot::fromPriceList(
            tld: $tld,
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
    public function calculateRetailPrice(string $tld, int $years, string $action = 'register'): ?float
    {
        $price = DomainPriceList::forTld($tld);

        if ($price === null) {
            return null;
        }

        $unitPrice = match ($action) {
            'transfer' => (float) ($price->transfer_price ?? $price->register_price),
            'renew'    => (float) $price->renew_price,
            default    => (float) $price->register_price,
        };

        return round($unitPrice * $years, 2);
    }

    /**
     * Get prices for all active TLDs (for the pricing table on the public page).
     *
     * @return Collection<int, DomainPriceSnapshot>
     */
    public function getAllActivePrices(): Collection
    {
        return DomainPriceList::active()
            ->orderByRaw("FIELD(tld, '.co.uk', '.uk', '.com', '.net', '.org') DESC")
            ->orderBy('tld')
            ->get()
            ->map(fn (DomainPriceList $item) => DomainPriceSnapshot::fromPriceList(
                tld: $item->tld,
                registerPrice: (float) $item->register_price,
                renewPrice: (float) $item->renew_price,
                transferPrice: $item->transfer_price !== null ? (float) $item->transfer_price : null,
                currency: $item->currency,
            ));
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
}
