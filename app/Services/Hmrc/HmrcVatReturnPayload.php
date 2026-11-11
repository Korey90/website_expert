<?php

namespace App\Services\Hmrc;

use App\Models\VatReturn;
use DomainException;

final class HmrcVatReturnPayload
{
    /** @var list<string> */
    public const FIELDS = [
        'vatDueSales', 'vatDueAcquisitions', 'totalVatDue',
        'vatReclaimedCurrPeriod', 'netVatDue', 'totalValueSalesExVAT',
        'totalValuePurchasesExVAT', 'totalValueGoodsSuppliedExVAT',
        'totalAcquisitionsExVAT',
    ];

    /** @return array<string, bool|int|float|string> */
    public function build(VatReturn $return, string $periodKey): array
    {
        if (preg_match('/^[A-Za-z0-9#]{1,18}$/', $periodKey) !== 1) {
            throw new DomainException(__('finance.vat_returns.errors.submission_payload'));
        }

        return [
            'periodKey' => $periodKey,
            ...$this->snapshotValues($return),
            'finalised' => true,
        ];
    }

    /** @param array<string, bool|int|float|string> $payload */
    public function hash(array $payload): string
    {
        return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
    }

    /**
     * @param  array<string, mixed>  $remote
     * @return array<string, int|float>
     */
    public function remoteValues(array $remote, string $periodKey): array
    {
        if (($remote['periodKey'] ?? null) !== $periodKey) {
            throw new DomainException(__('finance.vat_returns.errors.reconciliation_response'));
        }

        $normalized = [];

        foreach (self::FIELDS as $index => $field) {
            $value = $remote[$field] ?? null;

            if ((! is_int($value) && ! is_float($value)) || ($index >= 5 && ! is_int($value))) {
                throw new DomainException(__('finance.vat_returns.errors.reconciliation_response'));
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }

    /**
     * @param  array<string, int|float>  $expected
     * @param  array<string, int|float>  $remote
     */
    public function valuesMatch(array $expected, array $remote): bool
    {
        foreach (self::FIELDS as $index => $field) {
            if (! array_key_exists($field, $expected) || ! array_key_exists($field, $remote)) {
                return false;
            }

            $expectedValue = $index < 5
                ? number_format((float) $expected[$field], 2, '.', '')
                : (string) $expected[$field];
            $remoteValue = $index < 5
                ? number_format((float) $remote[$field], 2, '.', '')
                : (string) $remote[$field];

            if (! hash_equals($expectedValue, $remoteValue)) {
                return false;
            }
        }

        return true;
    }

    /** @return array<string, int|float> */
    private function snapshotValues(VatReturn $return): array
    {
        $values = data_get($return->snapshot, 'calculation.hmrc_values');

        if (! is_array($values) || array_diff(array_keys($values), self::FIELDS) !== []
            || array_diff(self::FIELDS, array_keys($values)) !== []) {
            throw new DomainException(__('finance.vat_returns.errors.submission_payload'));
        }

        $normalized = [];

        foreach (self::FIELDS as $index => $field) {
            $value = $values[$field];

            if ((! is_int($value) && ! is_float($value)) || ($index >= 5 && ! is_int($value))) {
                throw new DomainException(__('finance.vat_returns.errors.submission_payload'));
            }

            $normalized[$field] = $value;
        }

        return $normalized;
    }
}
