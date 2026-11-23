<?php

namespace App\Data\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditReportItem;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;

readonly class UniversalCreditSourceCandidate
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public Model $source,
        public string $eventKey,
        public string $direction,
        public string $category,
        public CarbonImmutable $cashDate,
        public ?CarbonImmutable $documentDate,
        public string $originalAmount,
        public string $originalCurrency,
        public ?int $grossAmountPence,
        public ?string $gbpExchangeRate = null,
        public ?string $exchangeRateSource = null,
        public ?CarbonImmutable $exchangeRateDate = null,
        public ?int $vatAmountPence = null,
        public ?string $vatSnapshotSource = null,
        public array $context = [],
    ) {}

    public function identityHash(UniversalCreditAssessmentPeriod $period): string
    {
        return UniversalCreditReportItem::identityHash(
            $period->business_id,
            (int) $period->id,
            $this->source->getMorphClass(),
            (int) $this->source->getKey(),
            $this->eventKey,
        );
    }

    public function fingerprint(): string
    {
        $payload = [
            'source_type' => $this->source->getMorphClass(),
            'source_id' => (int) $this->source->getKey(),
            'event_key' => $this->eventKey,
            'direction' => $this->direction,
            'category' => $this->category,
            'cash_date' => $this->cashDate->toDateString(),
            'document_date' => $this->documentDate?->toDateString(),
            'original_amount' => $this->originalAmount,
            'original_currency' => $this->originalCurrency,
            'gross_amount_pence' => $this->grossAmountPence,
            'gbp_exchange_rate' => $this->gbpExchangeRate,
            'exchange_rate_source' => $this->exchangeRateSource,
            'exchange_rate_date' => $this->exchangeRateDate?->toDateString(),
            'vat_amount_pence' => $this->vatAmountPence,
            'vat_snapshot_source' => $this->vatSnapshotSource,
            'context' => $this->canonicalize($this->context),
        ];

        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }

    private function canonicalize(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        foreach ($value as $key => $item) {
            $value[$key] = $this->canonicalize($item);
        }

        return $value;
    }
}
