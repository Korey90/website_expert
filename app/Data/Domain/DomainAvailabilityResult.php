<?php

namespace App\Data\Domain;

final readonly class DomainAvailabilityResult
{
    public function __construct(
        public string $domain,
        public bool $isAvailable,
        public bool $isPremium,
        public ?float $premiumPrice,
        public ?string $error,
        public ?string $reason = null,
    ) {}

    public static function available(string $domain, bool $isPremium = false, ?float $premiumPrice = null): self
    {
        return new self(
            domain: $domain,
            isAvailable: true,
            isPremium: $isPremium,
            premiumPrice: $premiumPrice,
            error: null,
        );
    }

    public static function unavailable(string $domain, ?string $reason = null): self
    {
        return new self(
            domain: $domain,
            isAvailable: false,
            isPremium: false,
            premiumPrice: null,
            error: null,
            reason: $reason,
        );
    }

    public static function error(string $domain, string $error): self
    {
        return new self(
            domain: $domain,
            isAvailable: false,
            isPremium: false,
            premiumPrice: null,
            error: $error,
        );
    }

    /**
     * Returns true when the registry signals it is temporarily busy/unreachable.
     * The OP sandbox often returns status "active" with reason "Registry is busy"
     * for domains that are actually free — a transient sandbox artefact.
     */
    public function isRegistryBusy(): bool
    {
        $reason = strtolower($this->reason ?? '');

        return str_contains($reason, 'busy') || str_contains($reason, 'not reachable');
    }

    public function toArray(): array
    {
        return [
            'domain'        => $this->domain,
            'is_available'  => $this->isAvailable,
            'is_premium'    => $this->isPremium,
            'premium_price' => $this->premiumPrice,
            'error'         => $this->error,
            'reason'        => $this->reason,
        ];
    }
}
