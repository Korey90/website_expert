<?php

namespace App\Services\Currency;

class CurrencyPriceCalculator
{
    public function roundRateUp(float $rate): float
    {
        $step = (float) config('currencies.rate_rounding.step', 0.10);

        if ($rate <= 0 || $step <= 0) {
            return 0.0;
        }

        return round(ceil($rate / $step) * $step, 2);
    }

    public function convertFromBase(float $amount, float $rate): float
    {
        return round($amount * $this->roundRateUp($rate), 2);
    }
}
