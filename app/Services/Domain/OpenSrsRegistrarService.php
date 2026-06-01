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
use RuntimeException;

/**
 * Placeholder for OpenSRS API integration.
 * Implement this class in Sprint 6 after registering a reseller account.
 *
 * @see https://opensrs.com/resources/documentation/opensrsapi/
 */
class OpenSrsRegistrarService implements DomainRegistrarInterface
{
    public function __construct()
    {
        throw new RuntimeException(
            'OpenSrsRegistrarService is not yet implemented. Set DOMAIN_REGISTRAR_PROVIDER=manual in .env.'
        );
    }

    public function search(string $query): DomainSearchResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function checkAvailability(string $domain): DomainAvailabilityResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function renew(string $domain, int $years): DomainRenewalResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function transfer(string $domain, string $authCode): DomainTransferResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function updateNameservers(string $domain, array $nameservers): bool
    {
        throw new RuntimeException('Not implemented.');
    }

    public function getDomainInfo(string $domain): DomainInfoResult
    {
        throw new RuntimeException('Not implemented.');
    }

    public function getPrice(string $tld): DomainPriceSnapshot
    {
        throw new RuntimeException('Not implemented.');
    }
}
