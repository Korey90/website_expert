<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditSimplifiedExpense;
use DomainException;

class UniversalCreditSimplifiedExpenseCalculator
{
    /**
     * @param  array<string, mixed>  $inputs
     * @param  array<string, mixed>  $rules
     * @return array{inputs: array<string, int|string>, amount_pence: int}
     */
    public function calculate(string $type, array $inputs, array $rules): array
    {
        return match ($type) {
            UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE => $this->vehicleMileage($inputs, $rules),
            UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING => $this->homeWorking($inputs, $rules),
            UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING => $this->businessPremisesLiving($inputs, $rules),
            default => throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_expense')),
        };
    }

    /** @return array{inputs: array<string, int|string>, amount_pence: int} */
    private function vehicleMileage(array $inputs, array $rules): array
    {
        $vehicleType = trim((string) ($inputs['vehicle_type'] ?? ''));
        $milesHundredths = $this->positiveHundredths($inputs['miles'] ?? null, 'invalid_mileage');

        if ($vehicleType === 'car_van_or_other_motor_vehicle') {
            $firstBandMiles = (int) data_get($rules, 'vehicle_mileage.car_van_or_other_motor_vehicle.first_band_miles', 0);
            $firstRate = (int) data_get($rules, 'vehicle_mileage.car_van_or_other_motor_vehicle.first_band_rate_pence', 0);
            $additionalRate = (int) data_get($rules, 'vehicle_mileage.car_van_or_other_motor_vehicle.additional_rate_pence', 0);

            if ($firstBandMiles <= 0 || $firstRate <= 0 || $additionalRate <= 0) {
                throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
            }

            $firstBandHundredths = $firstBandMiles * 100;
            $first = min($milesHundredths, $firstBandHundredths);
            $additional = max(0, $milesHundredths - $firstBandHundredths);
            $amount = $this->roundDivide(($first * $firstRate) + ($additional * $additionalRate), 100);
        } elseif ($vehicleType === 'motorbike_or_scooter') {
            $rate = (int) data_get($rules, 'vehicle_mileage.motorbike_or_scooter.rate_pence', 0);

            if ($rate <= 0) {
                throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
            }

            $amount = $this->roundDivide($milesHundredths * $rate, 100);
        } else {
            throw new DomainException(__('finance.universal_credit.errors.invalid_vehicle_type'));
        }

        return [
            'inputs' => [
                'vehicle_type' => $vehicleType,
                'miles_hundredths' => $milesHundredths,
            ],
            'amount_pence' => $amount,
        ];
    }

    /** @return array{inputs: array<string, int>, amount_pence: int} */
    private function homeWorking(array $inputs, array $rules): array
    {
        $hours = filter_var($inputs['hours'] ?? null, FILTER_VALIDATE_INT);
        $minimum = (int) data_get($rules, 'home_working.minimum_hours', 0);

        if ($hours === false || $hours < $minimum || $hours > 744 || $minimum <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_home_working_hours'));
        }

        $bands = data_get($rules, 'home_working.bands', []);

        if (! is_array($bands)) {
            throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
        }

        foreach ($bands as $band) {
            $bandMinimum = (int) ($band['minimum_hours'] ?? 0);
            $bandMaximum = isset($band['maximum_hours']) ? (int) $band['maximum_hours'] : null;

            if ($hours >= $bandMinimum && ($bandMaximum === null || $hours <= $bandMaximum)) {
                $amount = (int) ($band['amount_pence'] ?? 0);

                if ($amount <= 0) {
                    break;
                }

                return ['inputs' => ['hours' => $hours], 'amount_pence' => $amount];
            }
        }

        throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
    }

    /** @return array{inputs: array<string, int|string>, amount_pence: int} */
    private function businessPremisesLiving(array $inputs, array $rules): array
    {
        $actualCostPence = $this->positiveMoneyPence($inputs['actual_cost_gbp'] ?? null);
        $residentBand = trim((string) ($inputs['resident_band'] ?? ''));

        if (! in_array($residentBand, ['one_person', 'two_people', 'three_or_more_people'], true)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_resident_band'));
        }

        $deduction = (int) data_get($rules, "living_at_business_premises.monthly_deduction_pence.{$residentBand}", 0);

        if ($deduction <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.policy_integrity'));
        }

        return [
            'inputs' => [
                'actual_cost_pence' => $actualCostPence,
                'resident_band' => $residentBand,
                'private_use_deduction_pence' => $deduction,
            ],
            'amount_pence' => max(0, $actualCostPence - $deduction),
        ];
    }

    private function positiveHundredths(mixed $value, string $errorKey): int
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new DomainException(__("finance.universal_credit.errors.{$errorKey}"));
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $hundredths = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($hundredths <= 0) {
            throw new DomainException(__("finance.universal_credit.errors.{$errorKey}"));
        }

        return $hundredths;
    }

    private function positiveMoneyPence(mixed $value): int
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d{1,12}(?:\.\d{1,2})?$/', $value)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_amount'));
        }

        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');
        $pence = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        if ($pence <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_simplified_amount'));
        }

        return $pence;
    }

    private function roundDivide(int $numerator, int $denominator): int
    {
        return intdiv($numerator + intdiv($denominator, 2), $denominator);
    }
}
