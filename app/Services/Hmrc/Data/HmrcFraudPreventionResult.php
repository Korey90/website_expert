<?php

namespace App\Services\Hmrc\Data;

final readonly class HmrcFraudPreventionResult
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<int, string>  $missingHeaders
     */
    public function __construct(
        public array $headers,
        public array $missingHeaders,
    ) {}

    public function isComplete(): bool
    {
        return $this->missingHeaders === [];
    }
}
