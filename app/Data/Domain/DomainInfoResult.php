<?php

namespace App\Data\Domain;

use Illuminate\Support\Carbon;

final readonly class DomainInfoResult
{
    /**
     * @param  string[]  $nameservers
     */
    public function __construct(
        public string $domain,
        public string $status,
        public ?Carbon $registeredAt,
        public ?Carbon $expiresAt,
        public array $nameservers,
        public bool $autoRenew,
        public bool $whoisPrivacy,
        public ?string $error,
    ) {}

    public static function notFound(string $domain): self
    {
        return new self(
            domain: $domain,
            status: 'not_found',
            registeredAt: null,
            expiresAt: null,
            nameservers: [],
            autoRenew: false,
            whoisPrivacy: false,
            error: 'Domain not found at provider.',
        );
    }

    public function toArray(): array
    {
        return [
            'domain'        => $this->domain,
            'status'        => $this->status,
            'registered_at' => $this->registeredAt?->toIso8601String(),
            'expires_at'    => $this->expiresAt?->toIso8601String(),
            'nameservers'   => $this->nameservers,
            'auto_renew'    => $this->autoRenew,
            'whois_privacy' => $this->whoisPrivacy,
            'error'         => $this->error,
        ];
    }
}
