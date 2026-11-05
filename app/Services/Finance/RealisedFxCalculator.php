<?php

namespace App\Services\Finance;

use DomainException;

class RealisedFxCalculator
{
    public const INCOMING = 'incoming';

    public const OUTGOING = 'outgoing';

    /**
     * @return array{amount_gbp: string, carrying_amount_gbp: string, realised_fx_gbp: string}
     */
    public function calculate(
        mixed $amount,
        mixed $settlementRate,
        mixed $carryingRate,
        string $cashDirection,
    ): array {
        $amount = round((float) $amount, 2);
        $settlementRate = (float) $settlementRate;
        $carryingRate = (float) $carryingRate;

        if ($amount <= 0 || $settlementRate <= 0 || $carryingRate <= 0) {
            throw new DomainException('Positive amount, settlement rate and carrying rate are required.');
        }

        if (! in_array($cashDirection, [self::INCOMING, self::OUTGOING], true)) {
            throw new DomainException('The cash direction must be incoming or outgoing.');
        }

        $amountGbp = round($amount * $settlementRate, 2);
        $carryingAmountGbp = round($amount * $carryingRate, 2);
        $realisedFxGbp = $cashDirection === self::INCOMING
            ? round($amountGbp - $carryingAmountGbp, 2)
            : round($carryingAmountGbp - $amountGbp, 2);

        return [
            'amount_gbp' => number_format($amountGbp, 2, '.', ''),
            'carrying_amount_gbp' => number_format($carryingAmountGbp, 2, '.', ''),
            'realised_fx_gbp' => number_format($realisedFxGbp, 2, '.', ''),
        ];
    }
}
