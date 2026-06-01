<?php

namespace App\Data\Domain;

final readonly class DomainSearchResult
{
    /**
     * @param  DomainAvailabilityResult[]  $results
     */
    public function __construct(
        public string $query,
        public array $results,
    ) {}

    public function getAvailable(): array
    {
        return array_filter($this->results, fn (DomainAvailabilityResult $r) => $r->isAvailable);
    }

    public function toArray(): array
    {
        return [
            'query'   => $this->query,
            'results' => array_map(fn (DomainAvailabilityResult $r) => $r->toArray(), $this->results),
        ];
    }
}
