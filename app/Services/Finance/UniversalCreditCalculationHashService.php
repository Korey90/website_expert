<?php

namespace App\Services\Finance;

use DomainException;

class UniversalCreditCalculationHashService
{
    /** @param array<string, mixed> $snapshot */
    public function hash(array $snapshot): string
    {
        $encoded = json_encode(
            $this->canonicalize($snapshot),
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        if (! is_string($encoded)) {
            throw new DomainException(__('finance.universal_credit.errors.calculation_encode'));
        }

        return hash('sha256', $encoded);
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
