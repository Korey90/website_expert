<?php

namespace App\Services\Finance;

use App\Models\Business;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\VatAdjustment;
use Carbon\CarbonImmutable;
use DomainException;
use Throwable;

class VatAdjustmentPayloadValidator
{
    /** @param array<string, mixed> $data @return array<string, mixed> */
    public function normalize(Business $business, array $data): array
    {
        $type = $this->enum($data['adjustment_type'] ?? null, VatAdjustment::types(), 'VAT adjustment type');
        $side = $this->enum($data['vat_side'] ?? null, VatAdjustment::sides(), 'VAT side');
        $direction = $this->enum($data['direction'] ?? null, VatAdjustment::directions(), 'VAT adjustment direction');
        $method = $this->enum($data['correction_method'] ?? null, VatAdjustment::correctionMethods(), 'VAT correction method');
        $effectiveDate = $this->date($data['effective_date'] ?? null, 'effective date');
        $amount = $this->amount($data['amount_gbp'] ?? null);
        $reason = trim((string) ($data['reason'] ?? ''));
        $hmrcReference = trim((string) ($data['hmrc_reference'] ?? ''));
        $isDeliberate = filter_var($data['is_deliberate'] ?? false, FILTER_VALIDATE_BOOL);

        if (mb_strlen($reason) < 10 || mb_strlen($reason) > 2000) {
            throw new DomainException('A VAT adjustment reason between 10 and 2000 characters is required.');
        }

        if (mb_strlen($hmrcReference) > 255) {
            throw new DomainException('The HMRC reference must not exceed 255 characters.');
        }

        $taxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->effectiveOn($effectiveDate)
            ->find($this->positiveInteger($data['tax_code_id'] ?? null, 'tax code'));

        if (! $taxCode) {
            throw new DomainException('The VAT adjustment requires an effective tax code from the same business.');
        }

        $this->assertTaxCodeSupportsSide($taxCode, $side);

        $controlAccount = $this->account($business, $data['control_ledger_account_id'] ?? null, 'VAT control account');
        $expectedSubtype = $side === VatAdjustment::SIDE_OUTPUT ? 'tax_payable' : 'tax_receivable';

        if ($controlAccount->subtype !== $expectedSubtype) {
            throw new DomainException('The selected VAT control account does not match the adjustment side.');
        }

        $contraAccount = $this->account($business, $data['contra_ledger_account_id'] ?? null, 'contra account');

        if ($contraAccount->is($controlAccount) || in_array($contraAccount->subtype, ['tax_payable', 'tax_receivable'], true)) {
            throw new DomainException('The contra account must be a different non-VAT ledger account.');
        }

        $discoveredOn = null;
        $originalFrom = null;
        $originalTo = null;

        if ($type === VatAdjustment::TYPE_ACCOUNTING_ADJUSTMENT) {
            if ($method !== VatAdjustment::METHOD_NOT_ERROR || $isDeliberate) {
                throw new DomainException('A normal VAT accounting adjustment must use the not-error method.');
            }

            $hmrcReference = '';
        } else {
            if (! in_array($method, [VatAdjustment::METHOD_CURRENT_RETURN, VatAdjustment::METHOD_SEPARATE_NOTIFICATION], true)) {
                throw new DomainException('A VAT error correction requires a current-return or separate-notification method.');
            }

            $discoveredOn = $this->date($data['discovered_on'] ?? null, 'discovery date');
            $originalFrom = $this->date($data['original_period_from'] ?? null, 'original period start');
            $originalTo = $this->date($data['original_period_to'] ?? null, 'original period end');

            if ($originalFrom > $originalTo) {
                throw new DomainException('The original VAT period start must not be after its end.');
            }

            if ($discoveredOn > $effectiveDate || $originalTo >= $effectiveDate) {
                throw new DomainException('The discovery and original VAT period dates must precede the adjustment date.');
            }

            if ($isDeliberate && $method !== VatAdjustment::METHOD_SEPARATE_NOTIFICATION) {
                throw new DomainException('A deliberate VAT error must be reported separately to HMRC.');
            }

        }

        return [
            'adjustment_type' => $type,
            'vat_side' => $side,
            'direction' => $direction,
            'correction_method' => $method,
            'tax_code_id' => $taxCode->id,
            'control_ledger_account_id' => $controlAccount->id,
            'contra_ledger_account_id' => $contraAccount->id,
            'effective_date' => $effectiveDate,
            'discovered_on' => $discoveredOn,
            'original_period_from' => $originalFrom,
            'original_period_to' => $originalTo,
            'amount_gbp' => $amount,
            'is_deliberate' => $isDeliberate,
            'reason' => $reason,
            'hmrc_reference' => $hmrcReference !== '' ? $hmrcReference : null,
        ];
    }

    public function validateStored(VatAdjustment $adjustment): void
    {
        $business = Business::query()->findOrFail($adjustment->business_id);

        $this->normalize($business, [
            'adjustment_type' => $adjustment->adjustment_type,
            'vat_side' => $adjustment->vat_side,
            'direction' => $adjustment->direction,
            'correction_method' => $adjustment->correction_method,
            'tax_code_id' => $adjustment->tax_code_id,
            'control_ledger_account_id' => $adjustment->control_ledger_account_id,
            'contra_ledger_account_id' => $adjustment->contra_ledger_account_id,
            'effective_date' => $adjustment->effective_date?->toDateString(),
            'discovered_on' => $adjustment->discovered_on?->toDateString(),
            'original_period_from' => $adjustment->original_period_from?->toDateString(),
            'original_period_to' => $adjustment->original_period_to?->toDateString(),
            'amount_gbp' => $adjustment->amount_gbp,
            'is_deliberate' => $adjustment->is_deliberate,
            'reason' => $adjustment->reason,
            'hmrc_reference' => $adjustment->hmrc_reference,
        ]);
    }

    /** @param list<string> $allowed */
    private function enum(mixed $value, array $allowed, string $label): string
    {
        $normalized = trim((string) $value);

        if (! in_array($normalized, $allowed, true)) {
            throw new DomainException("Invalid {$label}.");
        }

        return $normalized;
    }

    private function date(mixed $value, string $label): string
    {
        $date = trim((string) $value);

        try {
            $parsed = CarbonImmutable::createFromFormat('!Y-m-d', $date);
        } catch (Throwable) {
            $parsed = null;
        }

        if (! $parsed || $parsed->toDateString() !== $date) {
            throw new DomainException("The {$label} must use a valid YYYY-MM-DD value.");
        }

        return $date;
    }

    private function amount(mixed $value): string
    {
        $amount = trim((string) $value);

        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $amount)) {
            throw new DomainException('The VAT adjustment amount must be a positive GBP amount with at most two decimal places.');
        }

        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');
        $normalized = (ltrim($whole, '0') ?: '0').'.'.str_pad($fraction, 2, '0');

        if ($normalized === '0.00') {
            throw new DomainException('The VAT adjustment amount must be positive.');
        }

        return $normalized;
    }

    private function positiveInteger(mixed $value, string $label): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (! $id) {
            throw new DomainException("The {$label} is required.");
        }

        return (int) $id;
    }

    private function account(Business $business, mixed $id, string $label): LedgerAccount
    {
        $account = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->activePostable()
            ->find($this->positiveInteger($id, $label));

        if (! $account) {
            throw new DomainException("The {$label} must be an active postable account from the same business.");
        }

        return $account;
    }

    private function assertTaxCodeSupportsSide(TaxCode $taxCode, string $side): void
    {
        $mapping = $taxCode->vat_return_mapping ?? [];

        if ($side === VatAdjustment::SIDE_OUTPUT) {
            $boxes = collect($mapping['output_vat'] ?? [])->map(fn (mixed $box): int => (int) $box)->unique()->values()->all();

            if (! $taxCode->output_eligible || count($boxes) !== 1 || ! in_array($boxes[0], [1, 2], true)) {
                throw new DomainException('The selected tax code does not provide one valid output VAT mapping.');
            }

            return;
        }

        $boxes = collect($mapping['input_vat'] ?? [])->map(fn (mixed $box): int => (int) $box)->unique()->values()->all();

        if (! $taxCode->input_eligible || $boxes !== [4]) {
            throw new DomainException('The selected tax code does not provide the required input VAT mapping to box 4.');
        }
    }
}
