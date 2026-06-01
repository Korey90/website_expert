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

    public static function unavailable(string $domain): self
    {
        return new self(
            domain: $domain,
            isAvailable: false,
            isPremium: false,
            premiumPrice: null,
            error: null,
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

    public function toArray(): array
    {
        return [
            'domain'        => $this->domain,
            'is_available'  => $this->isAvailable,
            'is_premium'    => $this->isPremium,
            'premium_price' => $this->premiumPrice,
            'error'         => $this->error,
        ];
    }
}
