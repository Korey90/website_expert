<?php

namespace App\Data\Domain;

use Illuminate\Support\Carbon;

final readonly class DomainRegistrationResult
{
    public function __construct(
        public bool $success,
        public ?string $providerId,
        public ?Carbon $registeredAt,
        public ?Carbon $expiresAt,
        public ?string $error,
    ) {}

    public static function success(
        string $providerId,
        ?Carbon $registeredAt = null,
        ?Carbon $expiresAt = null,
    ): self {
        return new self(
            success: true,
            providerId: $providerId,
            registeredAt: $registeredAt ?? now(),
            expiresAt: $expiresAt,
            error: null,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            providerId: null,
            registeredAt: null,
            expiresAt: null,
            error: $error,
        );
    }

    public function toArray(): array
    {
        return [
            'success'       => $this->success,
            'provider_id'   => $this->providerId,
            'registered_at' => $this->registeredAt?->toIso8601String(),
            'expires_at'    => $this->expiresAt?->toIso8601String(),
            'error'         => $this->error,
        ];
    }
}
