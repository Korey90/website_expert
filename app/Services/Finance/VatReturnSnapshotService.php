<?php

namespace App\Services\Finance;

use App\Actions\Finance\VatReturnCalculationAction;
use App\Models\AccountingProfile;
use DomainException;
use JsonException;

class VatReturnSnapshotService
{
    public const SCHEMA_VERSION = 1;

    public function __construct(private readonly VatReturnCalculationAction $calculator) {}

    /**
     * @return array{snapshot: array<string, mixed>, hash: string, source_journal_count: int, source_line_count: int, warning_count: int}
     */
    public function build(string $businessId, string $dateFrom, string $dateTo): array
    {
        $result = $this->calculator->execute($businessId, $dateFrom, $dateTo);

        if (! $result['status']->is_ready_for_review || $result['issues']->isNotEmpty()) {
            throw new DomainException(__('finance.vat_returns.errors.calculation_not_ready'));
        }

        $profile = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->firstOrFail();
        $rows = $result['vat_detail']['rows']->map(fn (object $row): array => [
            'journal_entry_id' => $row->journal_entry_id,
            'journal_line_ids' => array_values($row->journal_line_ids ?? []),
            'journal_snapshot_hash' => $row->journal_snapshot_hash ?? null,
            'tax_point' => $row->tax_point,
            'kind' => $row->kind,
            'reference' => $row->reference,
            'tax_code_id' => $row->tax_code_id,
            'tax_code' => $row->tax_code,
            'tax_code_type' => $row->tax_code_type,
            'tax_rate' => $row->tax_rate,
            'vat_return_mapping' => $row->vat_return_mapping,
            'net_sales_gbp' => $row->net_sales_gbp,
            'net_purchases_gbp' => $row->net_purchases_gbp,
            'output_vat_gbp' => $row->output_vat_gbp,
            'input_vat_gbp' => $row->input_vat_gbp,
            'is_reversal' => $row->is_reversal,
            'is_adjustment' => $row->is_adjustment,
            'is_vat_return_excluded' => $row->is_vat_return_excluded ?? false,
        ])->values()->all();
        $taxCodes = $result['vat_detail']['tax_codes']->map(fn (object $taxCode): array => [
            'tax_code_id' => $taxCode->tax_code_id,
            'code' => $taxCode->code,
            'type' => $taxCode->type,
            'rate' => $taxCode->rate,
            'vat_return_mapping' => $taxCode->vat_return_mapping,
            'is_active' => $taxCode->is_active,
            'net_sales_gbp' => $taxCode->net_sales_gbp,
            'net_purchases_gbp' => $taxCode->net_purchases_gbp,
            'output_vat_gbp' => $taxCode->output_vat_gbp,
            'input_vat_gbp' => $taxCode->input_vat_gbp,
        ])->values()->all();
        $vatNumber = strtoupper(preg_replace('/\s+/', '', (string) $profile->vat_number) ?? '');
        $snapshot = $this->canonicalize([
            'schema_version' => self::SCHEMA_VERSION,
            'period' => [
                'from' => $result['date_from'],
                'to' => $result['date_to'],
            ],
            'profile' => [
                'accounting_profile_id' => $profile->id,
                'functional_currency' => $profile->functional_currency,
                'vat_scheme' => $profile->vat_scheme,
                'vat_effective_from' => $profile->vat_effective_from?->toDateString(),
                'vat_effective_to' => $profile->vat_effective_to?->toDateString(),
                'vat_number_last_four' => $vatNumber === '' ? null : substr($vatNumber, -4),
                'vat_number_sha256' => $vatNumber === '' ? null : hash('sha256', $vatNumber),
            ],
            'calculation' => [
                'boxes' => $result['boxes']->map(fn (object $box): array => [
                    'number' => $box->number,
                    'hmrc_field' => $box->hmrc_field,
                    'pennies' => $box->pennies,
                    'raw_gbp' => $box->raw_gbp,
                    'submission_value' => $box->submission_value,
                    'decimal_places' => $box->decimal_places,
                    'is_derived' => $box->is_derived,
                ])->values()->all(),
                'hmrc_values' => get_object_vars($result['hmrc_values']),
                'checks' => get_object_vars($result['checks']),
                'net_direction' => $result['status']->net_direction,
                'signed_net_vat_gbp' => $result['status']->signed_net_vat_gbp,
                'issues' => $this->findings($result['issues']),
                'warnings' => $this->findings($result['warnings']),
            ],
            'sources' => [
                'rows' => $rows,
                'tax_codes' => $taxCodes,
            ],
        ]);
        $lineIds = collect($rows)->flatMap(fn (array $row): array => $row['journal_line_ids'])->unique();

        return [
            'snapshot' => $snapshot,
            'hash' => $this->hash($snapshot),
            'source_journal_count' => collect($rows)->pluck('journal_entry_id')->unique()->count(),
            'source_line_count' => $lineIds->count(),
            'warning_count' => $result['warnings']->count(),
        ];
    }

    /** @param array<string, mixed> $snapshot */
    public function hash(array $snapshot): string
    {
        try {
            $json = json_encode(
                $this->canonicalize($snapshot),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
        } catch (JsonException $exception) {
            throw new DomainException(__('finance.vat_returns.errors.snapshot_encode'), previous: $exception);
        }

        return hash('sha256', $json);
    }

    /** @return list<array{code: string, context: array<string, mixed>}> */
    private function findings(iterable $findings): array
    {
        return collect($findings)->map(fn (object $finding): array => [
            'code' => $finding->code,
            'context' => $finding->context,
        ])->values()->all();
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
