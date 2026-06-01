<?php

namespace App\Data\Domain;

use Illuminate\Support\Carbon;

final readonly class DomainPriceSnapshot
{
    public function __construct(
        public string $tld,
        public float $registerPrice,
        public float $renewPrice,
        public ?float $transferPrice,
        public string $currency,
        public Carbon $snapshotAt,
    ) {}

    public static function fromPriceList(
        string $tld,
        float $registerPrice,
        float $renewPrice,
        ?float $transferPrice,
        string $currency = 'GBP',
    ): self {
        return new self(
            tld: $tld,
            registerPrice: $registerPrice,
            renewPrice: $renewPrice,
            transferPrice: $transferPrice,
            currency: $currency,
            snapshotAt: now(),
        );
    }

    public function toArray(): array
    {
        return [
            'tld'            => $this->tld,
            'register_price' => $this->registerPrice,
            'renew_price'    => $this->renewPrice,
            'transfer_price' => $this->transferPrice,
            'currency'       => $this->currency,
            'snapshot_at'    => $this->snapshotAt->toIso8601String(),
        ];
    }
}
