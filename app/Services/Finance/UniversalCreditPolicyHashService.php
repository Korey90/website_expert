<?php

namespace App\Services\Finance;

use DomainException;
use JsonException;

class UniversalCreditPolicyHashService
{
    /** @param array<string, mixed> $rules */
    public function hash(array $rules): string
    {
        try {
            $json = json_encode(
                $this->canonicalize($rules),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new DomainException(__('finance.universal_credit.errors.policy_encode'), previous: $exception);
        }

        return hash('sha256', $json);
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
