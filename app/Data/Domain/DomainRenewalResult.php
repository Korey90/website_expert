<?php

namespace App\Data\Domain;

use Illuminate\Support\Carbon;

final readonly class DomainRenewalResult
{
    public function __construct(
        public bool $success,
        public ?Carbon $newExpiresAt,
        public ?string $error,
    ) {}

    public static function success(Carbon $newExpiresAt): self
    {
        return new self(
            success: true,
            newExpiresAt: $newExpiresAt,
            error: null,
        );
    }

    public static function failure(string $error): self
    {
        return new self(
            success: false,
            newExpiresAt: null,
            error: $error,
        );
    }

    public function toArray(): array
    {
        return [
            'success'        => $this->success,
            'new_expires_at' => $this->newExpiresAt?->toIso8601String(),
            'error'          => $this->error,
        ];
    }
}
