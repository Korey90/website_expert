<?php

namespace App\Services\Finance;

use DomainException;

class UniversalCreditFxSnapshotCalculator
{
    /** @return array{rate: string, amount_pence: int} */
    public function calculate(string $originalAmount, int|float|string $rate): array
    {
        $rate = trim((string) $rate);

        if (! preg_match('/^\d{1,8}(?:\.\d{1,8})?$/', $rate)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
        }

        [$rateWhole, $rateFraction] = array_pad(explode('.', $rate, 2), 2, '');
        $rateScaled = ((int) $rateWhole * 100000000) + (int) str_pad($rateFraction, 8, '0');

        if ($rateScaled <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
        }

        [$amountWhole, $amountFraction] = array_pad(explode('.', $originalAmount, 2), 2, '');
        $originalPence = ((int) $amountWhole * 100) + (int) str_pad($amountFraction, 2, '0');

        if ($originalPence <= 0 || $originalPence > intdiv(PHP_INT_MAX, $rateScaled)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
        }

        $numerator = $originalPence * $rateScaled;
        $amountPence = intdiv($numerator + 50000000, 100000000);

        if ($amountPence <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_fx_snapshot'));
        }

        return [
            'rate' => sprintf('%d.%08d', intdiv($rateScaled, 100000000), $rateScaled % 100000000),
            'amount_pence' => $amountPence,
        ];
    }
}
