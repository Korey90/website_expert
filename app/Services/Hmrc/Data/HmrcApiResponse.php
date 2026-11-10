<?php

namespace App\Services\Hmrc\Data;

final readonly class HmrcApiResponse
{
    /** @param array<string, mixed> $data */
    public function __construct(
        public int $status,
        public array $data,
        public ?string $correlationId,
    ) {}
}
