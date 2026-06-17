<?php

namespace App\Actions\Domain;

use App\Services\Domain\DomainPricingService;
use App\Services\Domain\DomainRegistrarInterface;
use App\Services\Domain\ManualDomainRegistrarService;

class CheckDomainAvailabilityAction
{
    public function __construct(
        private readonly DomainRegistrarInterface $registrar,
        private readonly ManualDomainRegistrarService $manual,
        private readonly DomainPricingService $pricing,
    ) {}

    /**
     * Search domain availability across all active TLDs.
     * Strips trailing TLD if user typed a full domain name.
     * Falls back to the manual (stub) service when the live registrar API fails.
     *
     * @return array<int, array{domain: string, name: string, tld: string, is_available: bool, is_premium: bool, register_price: float|null, renew_price: float|null, currency: string, error: string|null}>
     */
    public function execute(string $query): array
    {
        $query = strtolower(trim($query));

        // Strip any typed TLD (.co.uk, .com, etc.) so user can type "example" or "example.co.uk"
        $query = preg_replace('/\..*$/', '', $query);

        if ($query === '') {
            return [];
        }

        $searchResult = $this->registrar->search($query);

        // If the live registrar API returned only error results, fall back to the
        // manual stub so users still get a useful availability list.
        $allErrors = count($searchResult->results) > 0
            && collect($searchResult->results)->every(fn ($r) => $r->error !== null);

        if ($allErrors) {
            $searchResult = $this->manual->search($query);
        }

        $currency = $this->pricing->resolveCurrency();
        $rows = [];
        foreach ($searchResult->results as $availability) {
            // Derive TLD: strip base name from the full domain string
            $tld = substr($availability->domain, strlen($query));

            $row = [
                'domain' => $availability->domain,
                'name' => $query,
                'tld' => $tld,
                'is_available' => $availability->isAvailable,
                'is_premium' => $availability->isPremium,
                'register_price' => null,
                'renew_price' => null,
                'currency' => $currency,
                'error' => $availability->error,
            ];

            if ($availability->isAvailable) {
                try {
                    $snapshot = $this->pricing->getPriceForTld($tld, $currency);
                    if ($snapshot !== null) {
                        $row['register_price'] = $snapshot->registerPrice;
                        $row['renew_price'] = $snapshot->renewPrice;
                        $row['currency'] = $snapshot->currency;
                    }
                } catch (\Throwable) {
                    // Price not found — show as available without price
                }
            }

            $rows[] = $row;
        }

        // Available results first, then by preferred TLD order
        $preferred = ['.co.uk', '.uk', '.com', '.net', '.org'];
        usort($rows, static function (array $a, array $b) use ($preferred): int {
            if ($a['is_available'] !== $b['is_available']) {
                return $a['is_available'] ? -1 : 1;
            }
            $aIdx = array_search($a['tld'], $preferred, true);
            $bIdx = array_search($b['tld'], $preferred, true);

            return ($aIdx === false ? 99 : $aIdx) <=> ($bIdx === false ? 99 : $bIdx);
        });

        return $rows;
    }
}
