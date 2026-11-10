<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Services\Finance\VatDetailService;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class VatReturnCalculationAction
{
    /** @var array<string, string> */
    private const METRIC_FIELDS = [
        'output_vat' => 'output_vat_gbp',
        'input_vat' => 'input_vat_gbp',
        'net_sales' => 'net_sales_gbp',
        'net_purchases' => 'net_purchases_gbp',
    ];

    /** @var array<string, list<int>> */
    private const ALLOWED_BOXES = [
        'output_vat' => [1, 2],
        'input_vat' => [4],
        'net_sales' => [6, 8],
        'net_purchases' => [7, 9],
    ];

    /** @var array<int, string> */
    private const HMRC_FIELDS = [
        1 => 'vatDueSales',
        2 => 'vatDueAcquisitions',
        3 => 'totalVatDue',
        4 => 'vatReclaimedCurrPeriod',
        5 => 'netVatDue',
        6 => 'totalValueSalesExVAT',
        7 => 'totalValuePurchasesExVAT',
        8 => 'totalValueGoodsSuppliedExVAT',
        9 => 'totalAcquisitionsExVAT',
    ];

    public function __construct(
        private readonly VatDetailService $vatDetailService,
    ) {}

    /**
     * @return array{
     *     date_from: string,
     *     date_to: string,
     *     boxes: Collection<int, object>,
     *     hmrc_values: object,
     *     contributions: Collection<int, object>,
     *     issues: Collection<int, object>,
     *     warnings: Collection<int, object>,
     *     checks: object,
     *     status: object,
     *     vat_detail: array<string, mixed>
     * }
     */
    public function execute(string $businessId, string $dateFrom, string $dateTo): array
    {
        $detail = $this->vatDetailService->report($businessId, $dateFrom, $dateTo);
        $profile = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->first();
        $boxPennies = array_fill(1, 9, 0);
        $contributions = collect();
        $issues = collect();
        $warnings = collect();

        $this->validateProfile($profile, $detail['date_from'], $detail['date_to'], $issues);

        foreach ($detail['rows'] as $row) {
            $this->mapRow($row, $boxPennies, $contributions, $issues, $warnings);
        }

        $boxPennies[3] = $boxPennies[1] + $boxPennies[2];
        $signedNetVatPennies = $boxPennies[3] - $boxPennies[4];
        $boxPennies[5] = abs($signedNetVatPennies);

        $detailOutputPennies = $this->moneyToPennies($detail['totals']->output_vat_gbp);
        $detailInputPennies = $this->moneyToPennies($detail['totals']->input_vat_gbp);
        $mappedOutputPennies = $boxPennies[1] + $boxPennies[2];
        $mappedInputPennies = $boxPennies[4];

        if (! $detail['totals']->is_reconciled) {
            $this->addFinding($issues, 'vat_detail_unreconciled');
        }

        if ($mappedOutputPennies !== $detailOutputPennies) {
            $this->addFinding($issues, 'output_vat_not_fully_mapped', [
                'difference' => $this->penniesToMoney($mappedOutputPennies - $detailOutputPennies),
            ]);
        }

        if ($mappedInputPennies !== $detailInputPennies) {
            $this->addFinding($issues, 'input_vat_not_fully_mapped', [
                'difference' => $this->penniesToMoney($mappedInputPennies - $detailInputPennies),
            ]);
        }

        if ($detail['totals']->adjustment_count > 0) {
            $this->addFinding($warnings, 'ledger_adjustments_present', [
                'count' => $detail['totals']->adjustment_count,
            ]);
        }

        if ($detail['tax_codes']->contains(fn (object $taxCode): bool => ! $taxCode->is_active)) {
            $this->addFinding($warnings, 'inactive_tax_codes_present');
        }

        $boxes = collect(range(1, 9))->mapWithKeys(fn (int $box): array => [
            $box => (object) [
                'number' => $box,
                'hmrc_field' => self::HMRC_FIELDS[$box],
                'pennies' => $boxPennies[$box],
                'raw_gbp' => $this->penniesToMoney($boxPennies[$box]),
                'submission_value' => $box <= 5
                    ? $this->penniesToMoney($boxPennies[$box])
                    : intdiv($boxPennies[$box], 100),
                'decimal_places' => $box <= 5 ? 2 : 0,
                'is_derived' => in_array($box, [3, 5], true),
            ],
        ]);

        $checks = (object) [
            'vat_detail_reconciled' => (bool) $detail['totals']->is_reconciled,
            'output_vat_mapped' => $mappedOutputPennies === $detailOutputPennies,
            'input_vat_mapped' => $mappedInputPennies === $detailInputPennies,
            'box_3_formula_valid' => $boxPennies[3] === $boxPennies[1] + $boxPennies[2],
            'box_5_formula_valid' => $boxPennies[5] === abs($boxPennies[3] - $boxPennies[4]),
            'box_8_included_in_box_6' => $this->subsetMappingValid($contributions, 8, 6),
            'box_9_included_in_box_7' => $this->subsetMappingValid($contributions, 9, 7),
        ];

        return [
            'date_from' => $detail['date_from'],
            'date_to' => $detail['date_to'],
            'boxes' => $boxes,
            'hmrc_values' => (object) collect(self::HMRC_FIELDS)
                ->mapWithKeys(fn (string $field, int $box): array => [$field => $boxes->get($box)->submission_value])
                ->all(),
            'contributions' => $contributions,
            'issues' => $issues->unique(fn (object $issue): string => $issue->code.':'.json_encode($issue->context))->values(),
            'warnings' => $warnings->unique(fn (object $warning): string => $warning->code.':'.json_encode($warning->context))->values(),
            'checks' => $checks,
            'status' => (object) [
                'is_ready_for_review' => $issues->isEmpty(),
                'net_direction' => $signedNetVatPennies > 0 ? 'payable' : ($signedNetVatPennies < 0 ? 'repayable' : 'nil'),
                'signed_net_vat_gbp' => $this->penniesToMoney($signedNetVatPennies),
                'is_submission' => false,
            ],
            'vat_detail' => $detail,
        ];
    }

    /**
     * @param  array<int, int>  $boxPennies
     * @param  Collection<int, object>  $contributions
     * @param  Collection<int, object>  $issues
     * @param  Collection<int, object>  $warnings
     */
    private function mapRow(
        object $row,
        array &$boxPennies,
        Collection $contributions,
        Collection $issues,
        Collection $warnings,
    ): void {
        $mapping = is_array($row->vat_return_mapping ?? null) ? $row->vat_return_mapping : [];

        foreach (self::METRIC_FIELDS as $metric => $field) {
            $pennies = $this->moneyToPennies($row->{$field} ?? 0);

            if ($pennies === 0) {
                continue;
            }

            $mappedBoxes = $mapping[$metric] ?? [];

            if (! is_array($mappedBoxes)) {
                $this->addFinding($issues, 'invalid_mapping_shape', $this->rowContext($row, $metric));

                continue;
            }

            $normalizedBoxes = collect($mappedBoxes)
                ->filter(fn (mixed $box): bool => is_int($box) || (is_string($box) && ctype_digit($box)))
                ->map(fn (mixed $box): int => (int) $box)
                ->unique()
                ->values();
            $invalidBoxes = $normalizedBoxes->diff(self::ALLOWED_BOXES[$metric]);

            if ($normalizedBoxes->count() !== count($mappedBoxes) || $invalidBoxes->isNotEmpty()) {
                $this->addFinding($issues, 'invalid_mapping_box', [
                    ...$this->rowContext($row, $metric),
                    'boxes' => collect($mappedBoxes)->implode(', '),
                ]);
            }

            $validBoxes = $normalizedBoxes->intersect(self::ALLOWED_BOXES[$metric])->values();

            if ($metric === 'output_vat' && $validBoxes->count() > 1) {
                $this->addFinding($issues, 'output_vat_mapped_more_than_once', $this->rowContext($row, $metric));
            }

            if ($validBoxes->isEmpty()) {
                if (($row->tax_code_type ?? null) === 'outside_scope' && in_array($metric, ['net_sales', 'net_purchases'], true)) {
                    $this->addFinding($warnings, 'outside_scope_excluded', $this->rowContext($row, $metric));

                    continue;
                }

                $this->addFinding($issues, 'unmapped_amount', $this->rowContext($row, $metric));

                continue;
            }

            if ($validBoxes->contains(8) && ! $validBoxes->contains(6)) {
                $this->addFinding($issues, 'box_8_requires_box_6', $this->rowContext($row, $metric));
            }

            if ($validBoxes->contains(9) && ! $validBoxes->contains(7)) {
                $this->addFinding($issues, 'box_9_requires_box_7', $this->rowContext($row, $metric));
            }

            foreach ($validBoxes as $box) {
                $boxPennies[$box] += $pennies;
                $contributions->push((object) [
                    'box' => $box,
                    'metric' => $metric,
                    'pennies' => $pennies,
                    'amount_gbp' => $this->penniesToMoney($pennies),
                    'journal_entry_id' => $row->journal_entry_id ?? null,
                    'reference' => $row->reference ?? null,
                    'tax_code_id' => $row->tax_code_id ?? null,
                    'tax_code' => $row->tax_code ?? 'UNKNOWN',
                ]);
            }
        }
    }

    /** @param Collection<int, object> $issues */
    private function validateProfile(
        ?AccountingProfile $profile,
        string $dateFrom,
        string $dateTo,
        Collection $issues,
    ): void {
        if (! $profile) {
            $this->addFinding($issues, 'accounting_profile_missing');

            return;
        }

        if (! $profile->vat_registered) {
            $this->addFinding($issues, 'business_not_vat_registered');
        }

        if (strtoupper((string) $profile->functional_currency) !== 'GBP') {
            $this->addFinding($issues, 'functional_currency_not_gbp');
        }

        if (! filled($profile->vat_number)) {
            $this->addFinding($issues, 'vat_number_missing');
        }

        if (! in_array($profile->vat_scheme, ['standard', 'annual'], true)) {
            $this->addFinding($issues, in_array($profile->vat_scheme, ['cash', 'flat_rate'], true)
                ? 'vat_scheme_not_supported'
                : 'vat_scheme_missing', ['scheme' => $profile->vat_scheme ?? '—']);
        }

        if ($profile->vat_effective_from && $profile->vat_effective_from->toDateString() > $dateFrom) {
            $this->addFinding($issues, 'period_starts_before_vat_registration', [
                'date' => $profile->vat_effective_from->toDateString(),
            ]);
        }

        if ($profile->vat_effective_to && $profile->vat_effective_to->toDateString() < $dateTo) {
            $this->addFinding($issues, 'period_ends_after_vat_registration', [
                'date' => $profile->vat_effective_to->toDateString(),
            ]);
        }
    }

    /** @param Collection<int, object> $contributions */
    private function subsetMappingValid(Collection $contributions, int $subsetBox, int $totalBox): bool
    {
        return $contributions
            ->where('box', $subsetBox)
            ->every(function (object $contribution) use ($contributions, $totalBox): bool {
                return $contributions->contains(fn (object $candidate): bool => $candidate->box === $totalBox
                    && $candidate->metric === $contribution->metric
                    && $candidate->journal_entry_id === $contribution->journal_entry_id
                    && $candidate->tax_code_id === $contribution->tax_code_id
                    && $candidate->pennies === $contribution->pennies
                );
            });
    }

    /** @return array<string, mixed> */
    private function rowContext(object $row, string $metric): array
    {
        return [
            'reference' => $row->reference ?? '—',
            'tax_code' => $row->tax_code ?? 'UNKNOWN',
            'metric' => $metric,
        ];
    }

    /**
     * @param  Collection<int, object>  $findings
     * @param  array<string, mixed>  $context
     */
    private function addFinding(Collection $findings, string $code, array $context = []): void
    {
        $findings->push((object) [
            'code' => $code,
            'context' => $context,
        ]);
    }

    private function moneyToPennies(mixed $amount): int
    {
        $value = trim((string) ($amount ?? '0'));
        $negative = str_starts_with($value, '-');
        $unsigned = ltrim($value, '+-');

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $unsigned)) {
            throw new InvalidArgumentException('A VAT Return amount must have at most two fraction digits.');
        }

        [$whole, $fraction] = array_pad(explode('.', $unsigned, 2), 2, '');
        $pennies = ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');

        return $negative ? -$pennies : $pennies;
    }

    private function penniesToMoney(int $pennies): float
    {
        return round($pennies / 100, 2);
    }
}
