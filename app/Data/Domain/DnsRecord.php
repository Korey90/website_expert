<?php

namespace App\Data\Domain;

final readonly class DnsRecord
{
    public function __construct(
        public int    $id,
        public string $type,
        public string $name,
        public string $value,
        public int    $ttl,
        public int    $prio,
    ) {}

    public function toArray(): array
    {
        return [
            'id'    => $this->id,
            'type'  => $this->type,
            'name'  => $this->name,
            'value' => $this->value,
            'ttl'   => $this->ttl,
            'prio'  => $this->prio,
        ];
    }
}
