<?php

namespace App\Services\Domain;

use App\Data\Domain\DnsRecord;
use App\Data\Domain\DomainAvailabilityResult;
use App\Data\Domain\DomainInfoResult;
use App\Data\Domain\DomainPriceSnapshot;
use App\Data\Domain\DomainRegistrationPayload;
use App\Data\Domain\DomainRegistrationResult;
use App\Data\Domain\DomainRenewalResult;
use App\Data\Domain\DomainSearchResult;
use App\Data\Domain\DomainTransferResult;

interface DomainRegistrarInterface
{
    /**
     * Search for domain availability across multiple TLD suggestions.
     * Returns a list of availability results for the query + popular TLDs.
     */
    public function search(string $query): DomainSearchResult;

    /**
     * Check availability of a single fully-qualified domain name.
     */
    public function checkAvailability(string $domain): DomainAvailabilityResult;

    /**
     * Register a domain. Called after payment is confirmed.
     */
    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult;

    /**
     * Renew an existing domain for the given number of years.
     */
    public function renew(string $domain, int $years): DomainRenewalResult;

    /**
     * Transfer a domain using the auth/EPP code.
     */
    public function transfer(string $domain, string $authCode): DomainTransferResult;

    /**
     * Update nameservers for an active domain.
     *
     * @param  string[]  $nameservers
     */
    public function updateNameservers(string $domain, array $nameservers): bool;

    /**
     * Fetch current domain info from the registrar.
     */
    public function getDomainInfo(string $domain): DomainInfoResult;

    /**
     * Get the wholesale price snapshot for a TLD from the registrar.
     */
    public function getPrice(string $tld): DomainPriceSnapshot;

    /**
     * Fetch all DNS records for a domain zone.
     * @return DnsRecord[]
     */
    public function getDnsRecords(string $domain): array;

    /**
     * Create a DNS record. Returns array with 'id' of the new record.
     */
    public function createDnsRecord(string $domain, array $record): array;

    /**
     * Update an existing DNS record. The original record data is used to identify
     * the record in registrars that have no per-record IDs (e.g. Openprovider).
     */
    public function updateDnsRecord(string $domain, array $originalRecord, array $newRecord): bool;

    /**
     * Delete a DNS record identified by its data (registrars without record IDs).
     */
    public function deleteDnsRecord(string $domain, array $record): bool;
}
