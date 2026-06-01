<?php

namespace App\Services\Domain;

use App\Data\Domain\DomainAvailabilityResult;
use App\Data\Domain\DomainInfoResult;
use App\Data\Domain\DomainPriceSnapshot;
use App\Data\Domain\DomainRegistrationPayload;
use App\Data\Domain\DomainRegistrationResult;
use App\Data\Domain\DomainRenewalResult;
use App\Data\Domain\DomainSearchResult;
use App\Data\Domain\DomainTransferResult;
use App\Models\DomainPriceList;

/**
 * Fallback service — used when no live registrar API is configured or the
 * live API is unavailable.  Availability is determined via DNS NS-record
 * lookup (fast, ~50 ms per domain).  Prices come from the DomainPriceList
 * table populated by the admin.
 */
class ManualDomainRegistrarService implements DomainRegistrarInterface
{
    /** TLDs shown in the search results when no external API is available. */
    private const SUGGESTED_TLDS = [
        '.co.uk', '.uk', '.com', '.net', '.org',
        '.io', '.co', '.me', '.info', '.biz',
        '.dev', '.app', '.online', '.store', '.tech', '.ai',
    ];

    public function search(string $query): DomainSearchResult
    {
        $query = strtolower(trim($query));

        // Strip any TLD if user typed full domain
        $baseName = preg_replace('/\.[a-z.]+$/', '', $query);

        $results = array_map(
            fn (string $tld) => $this->checkAvailability("{$baseName}{$tld}"),
            self::SUGGESTED_TLDS,
        );

        return new DomainSearchResult(query: $query, results: $results);
    }

    public function checkAvailability(string $domain): DomainAvailabilityResult
    {
        $domain = strtolower(trim($domain));

        try {
            // NS records exist on virtually every registered domain.
            // Suppress warnings in case the DNS resolver times out.
            $isTaken = @checkdnsrr($domain . '.', 'NS') || @checkdnsrr($domain . '.', 'A');

            return $isTaken
                ? DomainAvailabilityResult::unavailable($domain)
                : DomainAvailabilityResult::available($domain);
        } catch (\Throwable) {
            // Network / resolver failure — optimistically show as available
            return DomainAvailabilityResult::available($domain);
        }
    }

    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult
    {
        // MVP: return a pending result — admin registers manually
        // The RegisterDomainJob will mark the order as "registering" and create the domain record
        return DomainRegistrationResult::success(
            providerId: 'manual-' . now()->format('YmdHis'),
            registeredAt: now(),
            expiresAt: now()->addYears($payload->years),
        );
    }

    public function renew(string $domain, int $years): DomainRenewalResult
    {
        return DomainRenewalResult::success(
            newExpiresAt: now()->addYears($years),
        );
    }

    public function transfer(string $domain, string $authCode): DomainTransferResult
    {
        return DomainTransferResult::success(providerId: 'manual-transfer-' . now()->format('YmdHis'));
    }

    public function updateNameservers(string $domain, array $nameservers): bool
    {
        // MVP: just return true — admin updates nameservers at the registrar
        return true;
    }

    public function getDomainInfo(string $domain): DomainInfoResult
    {
        return DomainInfoResult::notFound($domain);
    }

    public function getPrice(string $tld): DomainPriceSnapshot
    {
        $price = DomainPriceList::forTld($tld);

        if ($price === null) {
            // Fallback default price when TLD not in price list
            return DomainPriceSnapshot::fromPriceList(
                tld: $tld,
                registerPrice: 0.0,
                renewPrice: 0.0,
                transferPrice: null,
                currency: 'GBP',
            );
        }

        return DomainPriceSnapshot::fromPriceList(
            tld: $tld,
            registerPrice: (float) $price->register_price,
            renewPrice: (float) $price->renew_price,
            transferPrice: $price->transfer_price !== null ? (float) $price->transfer_price : null,
            currency: $price->currency,
        );
    }
}
