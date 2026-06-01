<?php

namespace App\Data\Domain;

final readonly class DomainTransferResult
{
    public function __construct(
        public bool $success,
        public ?string $providerId,
        public ?string $error,
    ) {}

    public static function success(string $providerId): self
    {
        return new self(
            success: true,
            providerId: $providerId,
            error: null,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            providerId: null,
            error: $error,
        );
    }

    public function toArray(): array
    {
        return [
            'success'     => $this->success,
            'provider_id' => $this->providerId,
            'error'       => $this->error,
        ];
    }
}
