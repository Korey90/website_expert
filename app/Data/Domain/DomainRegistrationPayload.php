<?php

namespace App\Data\Domain;

final readonly class DomainRegistrationPayload
{
    public function __construct(
        public string $domainName,  // e.g. "example"
        public string $tld,         // e.g. ".co.uk"
        public int $years,
        public string $registrantFirstName,
        public string $registrantLastName,
        public string $registrantEmail,
        public string $registrantPhone,
        public string $registrantAddressLine1,
        public ?string $registrantAddressLine2,
        public string $registrantCity,
        public ?string $registrantCounty,
        public string $registrantPostcode,
        public string $registrantCountryCode,
        public ?string $registrantOrganisation,
        public bool $whoisPrivacy,
        public bool $autoRenew,
        /** @var string[] */
        public array $nameservers,
    ) {}

    public function getFullDomain(): string
    {
        return $this->domainName . $this->tld;
    }

    public static function fromArray(array $data): self
    {
        return new self(
            domainName: $data['domain_name'],
            tld: $data['tld'],
            years: (int) ($data['years'] ?? 1),
            registrantFirstName: $data['registrant_first_name'],
            registrantLastName: $data['registrant_last_name'],
            registrantEmail: $data['registrant_email'],
            registrantPhone: $data['registrant_phone'] ?? '',
            registrantAddressLine1: $data['registrant_address_line1'],
            registrantAddressLine2: $data['registrant_address_line2'] ?? null,
            registrantCity: $data['registrant_city'],
            registrantCounty: $data['registrant_county'] ?? null,
            registrantPostcode: $data['registrant_postcode'],
            registrantCountryCode: $data['registrant_country_code'] ?? 'GB',
            registrantOrganisation: $data['registrant_organisation'] ?? null,
            whoisPrivacy: (bool) ($data['whois_privacy'] ?? true),
            autoRenew: (bool) ($data['auto_renew'] ?? false),
            nameservers: $data['nameservers'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'domain_name'                => $this->domainName,
            'tld'                        => $this->tld,
            'years'                      => $this->years,
            'registrant_first_name'      => $this->registrantFirstName,
            'registrant_last_name'       => $this->registrantLastName,
            'registrant_email'           => $this->registrantEmail,
            'registrant_phone'           => $this->registrantPhone,
            'registrant_address_line1'   => $this->registrantAddressLine1,
            'registrant_address_line2'   => $this->registrantAddressLine2,
            'registrant_city'            => $this->registrantCity,
            'registrant_county'          => $this->registrantCounty,
            'registrant_postcode'        => $this->registrantPostcode,
            'registrant_country_code'    => $this->registrantCountryCode,
            'registrant_organisation'    => $this->registrantOrganisation,
            'whois_privacy'              => $this->whoisPrivacy,
            'auto_renew'                 => $this->autoRenew,
            'nameservers'                => $this->nameservers,
        ];
    }
}
