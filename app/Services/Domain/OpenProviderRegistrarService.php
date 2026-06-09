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
use Illuminate\Support\Carbon;

/**
 * Openprovider reseller API integration.
 *
 * Credentials are read from config('services.domain_registrar.openprovider').
 * Set DOMAIN_REGISTRAR_PROVIDER=openprovider and the username/password env vars to activate.
 *
 * @see https://docs.openprovider.com/
 */
class OpenProviderRegistrarService implements DomainRegistrarInterface
{
    // Default Openprovider parking nameservers used when none are specified
    private const DEFAULT_NAMESERVERS = [
        'ns1.openprovider.eu',
        'ns2.openprovider.be',
        'ns3.openprovider.eu',
    ];

    public function __construct(private readonly OpenProviderClient $client) {}

    // ── DomainRegistrarInterface ──────────────────────────────────────────────

    /**
     * Search for a domain name across common TLDs (name suggestion + bulk check).
     */
    public function search(string $query): DomainSearchResult
    {
        // Strip any extension the user may have typed; search the bare label
        $label = explode('.', $query, 2)[0];

        try {
            // Openprovider limits to 10 domains per check request — batch if needed
            $batches = array_chunk($this->buildSearchDomains($label), 10);

            $results = [];
            foreach ($batches as $batch) {
                $data = $this->client->post('/domains/check', [
                    'domains'    => $batch,
                    'with_price' => false,
                ]);

                foreach ($data['results'] ?? [] as $r) {
                    // API returns domain as a plain string ("example.co.uk") not a nested object
                    $full = is_array($r['domain'])
                        ? $r['domain']['name'] . '.' . $r['domain']['extension']
                        : (string) $r['domain'];
                    if ($r['status'] === 'free' && empty($r['reason'] ?? null)) {
                        $results[] = DomainAvailabilityResult::available(
                            domain: $full,
                            isPremium: (bool) ($r['is_premium'] ?? false),
                            premiumPrice: isset($r['premium']['price']['create'])
                                ? (float) $r['premium']['price']['create']
                                : null,
                        );
                    } else {
                        $results[] = DomainAvailabilityResult::unavailable($full, $r['reason'] ?? null);
                    }
                }
            }

            return new DomainSearchResult($query, $results);
        } catch (\Throwable $e) {
            return new DomainSearchResult($query, [
                DomainAvailabilityResult::error($query, $e->getMessage()),
            ]);
        }
    }

    /**
     * Check availability of a single fully-qualified domain name.
     */
    public function checkAvailability(string $domain): DomainAvailabilityResult
    {
        [$name, $extension] = $this->splitDomain($domain);

        try {
            $data = $this->client->post('/domains/check', [
                'domains'    => [['name' => $name, 'extension' => $extension]],
                'with_price' => false,
            ]);

            $result = $data['results'][0] ?? null;
            if (! $result) {
                return DomainAvailabilityResult::error($domain, 'Empty response from Openprovider.');
            }

            $reason = $result['reason'] ?? null;

            // Even when status is "free", a non-empty reason may indicate a transient registry issue
            // (e.g. sandbox returns status="free" but reason="Registry is busy").
            // In that case treat as unavailable so callers can detect registry problems.
            if ($result['status'] === 'free' && empty($reason)) {
                return DomainAvailabilityResult::available(
                    domain: $domain,
                    isPremium: (bool) ($result['is_premium'] ?? false),
                    premiumPrice: isset($result['premium']['price']['create'])
                        ? (float) $result['premium']['price']['create']
                        : null,
                );
            }

            // 'active' / 'in use' / 'reserved' — or "free" with a non-empty reason (sandbox quirk)
            return DomainAvailabilityResult::unavailable($domain, $reason);
        } catch (\Throwable $e) {
            return DomainAvailabilityResult::error($domain, $e->getMessage());
        }
    }

    /**
     * Register a domain after payment has been confirmed.
     *
     * Creates (or finds) an Openprovider customer handle for the registrant,
     * then submits the registration order.
     */
    public function register(DomainRegistrationPayload $payload): DomainRegistrationResult
    {
        try {
            $handle      = $payload->ownerHandle ?? $this->resolveCustomerHandle($payload);
            $nameServers = $this->buildNameServers($payload->nameservers);
            $extension   = $this->tldExtension($payload->tld);

            // Domain registration can be slow on sandbox; use 60 s timeout.
            $data = $this->client->post('/domains', [
                'domain' => [
                    'name'      => $payload->domainName,
                    'extension' => $extension,
                ],
                'owner_handle'             => $handle,
                'admin_handle'             => $handle,
                'tech_handle'              => $handle,
                'period'                   => $payload->years,
                'unit'                     => 'y',
                'name_servers'             => $nameServers,
                'autorenew'                => $payload->autoRenew ? 'on' : 'off',
                'is_private_whois_enabled' => $payload->whoisPrivacy,
            ], 60);

            return DomainRegistrationResult::success(
                providerId: (string) ($data['id'] ?? ''),
                registeredAt: isset($data['activation_date'])
                    ? Carbon::parse($data['activation_date'])
                    : now(),
                expiresAt: isset($data['expiration_date'])
                    ? Carbon::parse($data['expiration_date'])
                    : now()->addYears($payload->years),
            );
        } catch (\RuntimeException $e) {
            // code 10 = registry temporarily unreachable; OP queues the request and retries.
            // The domain entry exists in their system (status REQ) — look it up to get the ID.
            if (str_contains($e->getMessage(), '"code":10') || str_contains($e->getMessage(), 'Registry currently not reachable')) {
                return $this->resolveQueuedRegistration($payload);
            }

            return DomainRegistrationResult::failure($e->getMessage());
        } catch (\Throwable $e) {
            // cURL timeout (error 28): the registration request may have reached OP before the
            // connection dropped. Treat it like code 10 — try to look up the domain in OP.
            if (str_contains($e->getMessage(), 'cURL error 28') || str_contains($e->getMessage(), 'Operation timed out')) {
                return $this->resolveQueuedRegistration($payload);
            }

            return DomainRegistrationResult::failure($e->getMessage());
        }
    }

    /**
     * Called when OP returns code 10 (registry unreachable) or when the HTTP connection
     * times out after the request was already sent. In both cases OP may have queued or
     * even activated the domain. Look it up without a status filter so both REQ and ACT
     * domains are found. Returns an empty providerId if the domain is not yet visible.
     */
    private function resolveQueuedRegistration(DomainRegistrationPayload $payload): DomainRegistrationResult
    {
        $extension = $this->tldExtension($payload->tld);

        try {
            $data = $this->client->get('/domains', [
                'domain_name_pattern' => $payload->domainName,
                'extension'           => $extension,
                'limit'               => 1,
            ]);

            $entry = $data['results'][0] ?? null;

            if ($entry && ! empty($entry['id'])) {
                return DomainRegistrationResult::success(
                    providerId:  (string) $entry['id'],
                    registeredAt: isset($entry['order_date'])
                        ? Carbon::parse($entry['order_date'])
                        : now(),
                    expiresAt: isset($entry['expiration_date'])
                        ? Carbon::parse($entry['expiration_date'])
                        : now()->addYears($payload->years),
                );
            }
        } catch (\Throwable) {
            // Lookup failed — fall through to empty-providerId result
        }

        // Domain is queued but not yet visible via API — return success with empty ID.
        // The ID can be resolved later once the registry activates the domain.
        return DomainRegistrationResult::success(
            providerId: '',
            registeredAt: now(),
            expiresAt: now()->addYears($payload->years),
        );
    }

    /**
     * Renew a domain for the given number of years.
     *
     * Looks up the Openprovider integer domain ID from the stored provider_domain_id.
     */
    public function renew(string $domain, int $years): DomainRenewalResult
    {
        try {
            $domainId = $this->findDomainId($domain);

            $data = $this->client->post("/domains/{$domainId}/renew", [
                'period' => $years,
                'unit'   => 'y',
            ]);

            $expiresAt = isset($data['expiration_date'])
                ? Carbon::parse($data['expiration_date'])
                : now()->addYears($years);

            return DomainRenewalResult::success($expiresAt);
        } catch (\Throwable $e) {
            return DomainRenewalResult::failure($e->getMessage());
        }
    }

    /**
     * Initiate a domain transfer-in.
     */
    public function transfer(string $domain, string $authCode): DomainTransferResult
    {
        // For transfer we need a registrant payload — use a minimal placeholder handle.
        // In practice the caller should pass a payload; this method signature only
        // provides domain + authCode so we simply forward without a handle for now.
        try {
            [$name, $extension] = $this->splitDomain($domain);

            $data = $this->client->post('/domains/transfer', [
                'auth_code'   => $authCode,
                'domain'      => ['name' => $name, 'extension' => $extension],
                'name_servers'=> $this->buildNameServers([]),
                'autorenew'   => 'default',
            ]);

            return DomainTransferResult::success((string) ($data['id'] ?? ''));
        } catch (\Throwable $e) {
            return DomainTransferResult::failure($e->getMessage());
        }
    }

    /**
     * Update the nameservers for an existing domain.
     */
    public function updateNameservers(string $domain, array $nameservers): bool
    {
        try {
            $domainId = $this->findDomainId($domain);

            $this->client->put("/domains/{$domainId}", [
                'name_servers' => $this->buildNameServers($nameservers),
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Fetch live domain information from Openprovider.
     */
    public function getDomainInfo(string $domain): DomainInfoResult
    {
        try {
            $domainId = $this->findDomainId($domain);
            $data     = $this->client->get("/domains/{$domainId}");

            return new DomainInfoResult(
                domain: $domain,
                status: $data['status'] ?? 'unknown',
                registeredAt: isset($data['activation_date'])
                    ? Carbon::parse($data['activation_date'])
                    : null,
                expiresAt: isset($data['expiration_date'])
                    ? Carbon::parse($data['expiration_date'])
                    : null,
                nameservers: array_map(
                    fn (array $ns) => $ns['name'],
                    $data['name_servers'] ?? []
                ),
                autoRenew: ($data['autorenew'] ?? 'off') === 'on',
                whoisPrivacy: (bool) ($data['is_private_whois_enabled'] ?? false),
                error: null,
            );
        } catch (\Throwable $e) {
            return DomainInfoResult::notFound($domain);
        }
    }

    /**
     * Fetch registration/renewal price for a TLD from Openprovider.
     */
    public function getPrice(string $tld): DomainPriceSnapshot
    {
        $extension = $this->tldExtension($tld);

        try {
            // Correct OP endpoint: GET /domains/prices?domain.name=test&domain.extension=com&operation=create
            // Both domain.name and domain.extension are required; without domain.name the sandbox
            // returns code 302 "Your domain request has more than 63 characters!"
            $data = $this->client->get('/domains/prices', [
                'domain.name'      => 'test',
                'domain.extension' => $extension,
                'operation'        => 'create',
            ]);

            // Response shape: data.price.reseller.{price, currency}
            $resellerPrice = $data['price']['reseller'] ?? [];
            $productPrice  = $data['price']['product']  ?? [];

            $regPrice = (float) ($resellerPrice['price'] ?? $productPrice['price'] ?? 0);
            $currency = $resellerPrice['currency'] ?? $productPrice['currency'] ?? 'EUR';

            // Renewal price: try /domains/prices with operation=renew
            $renewData  = $this->client->get('/domains/prices', [
                'domain.name'      => 'test',
                'domain.extension' => $extension,
                'operation'        => 'renew',
            ]);
            $renewReseller = $renewData['price']['reseller'] ?? [];
            $renewProduct  = $renewData['price']['product']  ?? [];
            $renewPrice    = (float) ($renewReseller['price'] ?? $renewProduct['price'] ?? $regPrice);

            return DomainPriceSnapshot::fromPriceList(
                tld: $tld,
                registerPrice: $regPrice,
                renewPrice: $renewPrice,
                transferPrice: null,
                currency: $currency,
            );
        } catch (\Throwable $e) {
            // Return zero-price on failure; the system falls back to its own price list
            return DomainPriceSnapshot::fromPriceList(
                tld: $tld,
                registerPrice: 0.0,
                renewPrice: 0.0,
                transferPrice: null,
                currency: 'EUR',
            );
        }
    }

    // ── DNS record management ─────────────────────────────────────────────────

    public function getDnsRecords(string $domain): array
    {
        try {
            $data = $this->client->get("/dns/zones/{$domain}/records");

            return array_map(
                fn (array $r) => new DnsRecord(
                    id:    0, // OP has no record IDs — FetchDnsRecordsAction assigns array index
                    type:  strtoupper($r['type'] ?? 'A'),
                    name:  $r['name'] ?? '@',
                    value: $r['value'] ?? '',
                    ttl:   (int) ($r['ttl'] ?? 3600),
                    prio:  (int) ($r['prio'] ?? 0),
                ),
                $data['results'] ?? []
            );
        } catch (\Throwable) {
            return [];
        }
    }

    public function createDnsRecord(string $domain, array $record): array
    {
        $this->client->put("/dns/zones/{$domain}", [
            'records' => [
                'add' => [$this->formatRecord($record)],
            ],
        ]);

        return ['id' => 0];
    }

    public function updateDnsRecord(string $domain, array $originalRecord, array $newRecord): bool
    {
        try {
            $this->client->put("/dns/zones/{$domain}", [
                'records' => [
                    'remove' => [$this->formatRecord($originalRecord)],
                    'add'    => [$this->formatRecord($newRecord)],
                ],
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    public function deleteDnsRecord(string $domain, array $record): bool
    {
        try {
            $this->client->put("/dns/zones/{$domain}", [
                'records' => [
                    'remove' => [$this->formatRecord($record)],
                ],
            ]);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function formatRecord(array $record): array
    {
        return [
            'type'  => strtoupper($record['type']),
            'name'  => $record['name'],
            'value' => $record['value'],
            'ttl'   => (int) ($record['ttl'] ?? 3600),
            'prio'  => (int) ($record['prio'] ?? 0),
        ];
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Split a FQDN like "example.co.uk" into ["example", "co.uk"].
     * Also accepts a TLD with leading dot like ".co.uk" → extension = "co.uk".
     */
    private function splitDomain(string $domain): array
    {
        $domain = ltrim($domain, '.');
        $pos    = strpos($domain, '.');

        if ($pos === false) {
            return [$domain, ''];
        }

        return [substr($domain, 0, $pos), substr($domain, $pos + 1)];
    }

    /**
     * Strip the leading dot from a TLD so ".co.uk" becomes "co.uk".
     */
    private function tldExtension(string $tld): string
    {
        return ltrim($tld, '.');
    }

    /**
     * Convert an array of nameserver hostnames to the Openprovider format.
     */
    private function buildNameServers(array $nameservers): array
    {
        $list = array_filter($nameservers);

        if (empty($list)) {
            $list = self::DEFAULT_NAMESERVERS;
        }

        return array_map(fn (string $ns) => ['name' => $ns], array_values($list));
    }

    /**
     * Build a list of {name, extension} objects to check in search().
     * Uses a curated list of popular TLDs.
     */
    private function buildSearchDomains(string $label): array
    {
        $tlds = [
            'co.uk', 'uk', 'com', 'net', 'org',
            'io', 'co', 'me', 'info', 'biz',
            'dev', 'app', 'online', 'store', 'tech', 'ai',
        ];

        return array_map(fn (string $tld) => [
            'name'      => $label,
            'extension' => $tld,
        ], $tlds);
    }

    /**
     * Look up the Openprovider integer domain ID by querying the API.
     *
     * The argument may be a FQDN ("example.co.uk") or the already-known integer
     * provider_domain_id stored as a string ("123456") — if it's numeric we use it directly.
     */
    private function findDomainId(string $domainOrId): int
    {
        if (is_numeric($domainOrId)) {
            return (int) $domainOrId;
        }

        [$name, $extension] = $this->splitDomain($domainOrId);

        $data = $this->client->get('/domains', [
            'domain_name_pattern' => $name,
            'extension'           => $extension,
            'limit'               => 1,
        ]);

        $id = $data['results'][0]['id'] ?? null;

        if (! $id) {
            throw new \RuntimeException("Openprovider: domain '{$domainOrId}' not found.");
        }

        return (int) $id;
    }

    /**
     * Find an existing Openprovider customer by e-mail or create a new one.
     * Returns the handle string (e.g. "AB123456-XX").
     */
    private function resolveCustomerHandle(DomainRegistrationPayload $payload): string
    {
        // Try to find an existing customer with this e-mail address
        try {
            $data = $this->client->get('/customers', [
                'email_pattern' => $payload->registrantEmail,
                'limit'         => 1,
            ]);

            if (! empty($data['results'])) {
                return (string) $data['results'][0]['handle'];
            }
        } catch (\Throwable) {
            // If the lookup fails, fall through and create a new customer
        }

        return $this->createCustomerHandle($payload);
    }

    /**
     * Create a new Openprovider customer from the registrant data in the payload.
     */
    private function createCustomerHandle(DomainRegistrationPayload $payload): string
    {
        $phone = $this->parsePhone($payload->registrantPhone, $payload->registrantCountryCode);

        $body = [
            'name'    => [
                'first_name' => $payload->registrantFirstName,
                'last_name'  => $payload->registrantLastName,
            ],
            'email'   => $payload->registrantEmail,
            'phone'   => $phone,
            'address' => [
                'street'   => $payload->registrantAddressLine1,
                'number'   => $payload->registrantAddressLine2 ?? '',
                'city'     => $payload->registrantCity,
                'province' => $payload->registrantCounty ?? '',
                'zipcode'  => $payload->registrantPostcode,
                'country'  => strtoupper($payload->registrantCountryCode),
            ],
        ];

        if ($payload->registrantOrganisation) {
            $body['company_name'] = $payload->registrantOrganisation;
        }

        $data = $this->client->post('/customers', $body);

        return (string) $data['handle'];
    }

    /**
     * Parse a phone number string into the Openprovider format.
     *
     * Supports E.164 strings like "+44 7700 900123" and bare local numbers.
     * Falls back to a country-code lookup when no + prefix is present.
     */
    private function parsePhone(string $phone, string $isoCountry = 'GB'): array
    {
        $phone = trim($phone);

        if (str_starts_with($phone, '+')) {
            if (preg_match('/^\+(\d{1,3})[\s\-.]*([\d\s\-.]+)$/', $phone, $m)) {
                return [
                    'country_code'      => $m[1],
                    'area_code'         => '0',
                    'subscriber_number' => preg_replace('/\D/', '', $m[2]),
                ];
            }
        }

        // Fallback: derive country calling code from ISO-3166-1 alpha-2
        $cc = match (strtoupper($isoCountry)) {
            'US', 'CA'           => '1',
            'GB'                 => '44',
            'DE'                 => '49',
            'FR'                 => '33',
            'PL'                 => '48',
            'PT'                 => '351',
            'ES'                 => '34',
            'IT'                 => '39',
            'NL'                 => '31',
            'SE'                 => '46',
            'NO'                 => '47',
            'DK'                 => '45',
            'FI'                 => '358',
            'IE'                 => '353',
            default              => '1',
        };

        return [
            'country_code'      => $cc,
            'area_code'         => '0',
            'subscriber_number' => preg_replace('/\D/', '', $phone),
        ];
    }
}

