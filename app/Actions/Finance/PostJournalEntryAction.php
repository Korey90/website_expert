<?php

namespace App\Actions\Finance;

use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\Client;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Finance\VatReturnPeriodGuard;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JsonException;

class PostJournalEntryAction
{
    public function __construct(private readonly VatReturnPeriodGuard $vatReturnPeriodGuard) {}

    /**
     * @param  array{
     *     entry_date: string,
     *     description: string,
     *     idempotency_key: string,
     *     source?: Model|null,
     *     reversal_of?: JournalEntry|null,
     *     source_snapshot?: mixed,
     *     lines: list<array<string, mixed>>
     * }  $data
     */
    public function execute(Business $business, User $actor, array $data): JournalEntry
    {
        $this->assertAuthorized($business, $actor);
        $normalized = $this->normalizePayload($business, $data);
        $hash = $this->payloadHash($normalized);

        return DB::transaction(function () use ($business, $actor, $normalized, $hash): JournalEntry {
            Business::query()->lockForUpdate()->findOrFail($business->id);

            $existing = JournalEntry::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('idempotency_key', $normalized['idempotency_key'])
                ->first();

            if ($existing) {
                if (! hash_equals((string) $existing->source_snapshot_hash, $hash)) {
                    throw new DomainException('The idempotency key was already used for a different journal payload.');
                }

                return $existing->load('lines');
            }

            $period = AccountingPeriod::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->containing($normalized['entry_date'])
                ->lockForUpdate()
                ->first();

            if (! $period) {
                throw new DomainException('No accounting period contains the journal date.');
            }

            if (! $period->isWritable()) {
                throw new DomainException("Accounting period [{$period->name}] is {$period->status}.");
            }

            $this->vatReturnPeriodGuard->assertWritable($business->id, $normalized['entry_date']);

            $this->validateTenantReferences(
                $business,
                $normalized['entry_date'],
                $normalized['source'],
                $normalized['lines'],
                $normalized['reversal_of_id'] ?? null,
            );

            $entry = JournalEntry::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'accounting_period_id' => $period->id,
                'reversal_of_id' => $normalized['reversal_of_id'] ?? null,
                'entry_date' => $normalized['entry_date'],
                'entry_number' => $this->nextEntryNumber($business, $normalized['entry_date']),
                'source_type' => $normalized['source']?->getMorphClass(),
                'source_id' => $normalized['source']?->getKey(),
                'status' => JournalEntry::STATUS_DRAFT,
                'currency' => 'GBP',
                'description' => $normalized['description'],
                'idempotency_key' => $normalized['idempotency_key'],
                'source_snapshot_hash' => $hash,
                'created_by' => $actor->id,
            ]);

            foreach ($normalized['lines'] as $index => $line) {
                $entry->lines()->create([
                    ...$line,
                    'business_id' => $business->id,
                    'line_number' => $index + 1,
                ]);
            }

            $entry->update([
                'status' => JournalEntry::STATUS_POSTED,
                'posted_by' => $actor->id,
                'posted_at' => now(),
            ]);

            return $entry->fresh('lines');
        }, 3);
    }

    private function assertAuthorized(Business $business, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $business->id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the journal business.');
        }

        if (! PermissionHelper::allows($actor, 'post_finance')) {
            throw new DomainException('The actor cannot post journal entries.');
        }
    }

    /** @param array<string, mixed> $data */
    private function normalizePayload(Business $business, array $data): array
    {
        $entryDate = trim((string) ($data['entry_date'] ?? ''));
        $description = trim((string) ($data['description'] ?? ''));
        $idempotencyKey = trim((string) ($data['idempotency_key'] ?? ''));
        $lines = $data['lines'] ?? null;

        if ($entryDate === '' || $description === '' || $idempotencyKey === '') {
            throw new DomainException('Journal date, description and idempotency key are required.');
        }

        try {
            $parsedDate = CarbonImmutable::createFromFormat('!Y-m-d', $entryDate);
        } catch (\Throwable) {
            $parsedDate = null;
        }

        if (! $parsedDate || $parsedDate->format('Y-m-d') !== $entryDate) {
            throw new DomainException('Journal date must use a valid YYYY-MM-DD value.');
        }

        if (strlen($description) > 255 || strlen($idempotencyKey) > 150) {
            throw new DomainException('Journal description or idempotency key is too long.');
        }

        if (! is_array($lines) || count($lines) < 2) {
            throw new DomainException('A journal entry requires at least two lines.');
        }

        $normalizedLines = [];
        $debitTotal = 0;
        $creditTotal = 0;

        foreach (array_values($lines) as $line) {
            if (! is_array($line)) {
                throw new DomainException('Every journal line must be an object.');
            }

            $debit = $this->moneyToMinorUnits($line['debit_gbp'] ?? 0, 'debit_gbp');
            $credit = $this->moneyToMinorUnits($line['credit_gbp'] ?? 0, 'credit_gbp');

            if (($debit > 0) === ($credit > 0)) {
                throw new DomainException('Each journal line must contain exactly one positive debit or credit.');
            }

            $accountId = filter_var($line['ledger_account_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

            if (! $accountId) {
                throw new DomainException('Every journal line requires a ledger account.');
            }

            $originalCurrency = strtoupper(trim((string) ($line['original_currency'] ?? 'GBP')));
            if (! preg_match('/^[A-Z]{3}$/', $originalCurrency)) {
                throw new DomainException('Original currency must be a three-letter ISO code.');
            }

            $originalAmount = array_key_exists('original_amount', $line) && $line['original_amount'] !== null
                ? $this->moneyToDecimal($line['original_amount'], 'original_amount', false)
                : null;
            $exchangeRate = array_key_exists('exchange_rate', $line) && $line['exchange_rate'] !== null
                ? $this->positiveDecimal($line['exchange_rate'], 'exchange_rate', 8)
                : null;

            if ($originalCurrency !== 'GBP' && ($originalAmount === null || $exchangeRate === null)) {
                throw new DomainException('Foreign-currency lines require original amount and exchange rate snapshots.');
            }

            $dimensions = $line['dimensions'] ?? null;
            if ($dimensions !== null && ! is_array($dimensions)) {
                throw new DomainException('Journal line dimensions must be an object.');
            }

            $memo = isset($line['memo']) ? trim((string) $line['memo']) : null;
            if ($memo !== null && strlen($memo) > 255) {
                throw new DomainException('Journal line memo is too long.');
            }

            $debitTotal += $debit;
            $creditTotal += $credit;
            $normalizedLines[] = [
                'ledger_account_id' => (int) $accountId,
                'tax_code_id' => $this->nullablePositiveInteger($line['tax_code_id'] ?? null, 'tax_code_id'),
                'client_id' => $this->nullablePositiveInteger($line['client_id'] ?? null, 'client_id'),
                'supplier_id' => $this->nullablePositiveInteger($line['supplier_id'] ?? null, 'supplier_id'),
                'project_id' => $this->nullablePositiveInteger($line['project_id'] ?? null, 'project_id'),
                'debit_gbp' => $this->minorUnitsToDecimal($debit),
                'credit_gbp' => $this->minorUnitsToDecimal($credit),
                'original_currency' => $originalCurrency,
                'original_amount' => $originalAmount,
                'exchange_rate' => $exchangeRate,
                'memo' => $memo,
                'dimensions' => $dimensions,
            ];
        }

        if ($debitTotal === 0 || $debitTotal !== $creditTotal) {
            throw new DomainException('Journal entry debits and credits must balance in GBP.');
        }

        $source = $data['source'] ?? null;
        if ($source !== null && ! $source instanceof Model) {
            throw new DomainException('Journal source must be an Eloquent model.');
        }

        $reversalOf = $data['reversal_of'] ?? null;
        if ($reversalOf !== null && ! $reversalOf instanceof JournalEntry) {
            throw new DomainException('A journal reversal must reference its original journal entry.');
        }

        if ($reversalOf) {
            $this->assertReversalPayload($business, $entryDate, $source, $reversalOf, $normalizedLines);
        }

        return [
            'business_id' => $business->id,
            'entry_date' => $entryDate,
            'description' => $description,
            'idempotency_key' => $idempotencyKey,
            'source' => $source,
            'source_type' => $source?->getMorphClass(),
            'source_id' => $source?->getKey(),
            'source_snapshot' => $data['source_snapshot'] ?? null,
            'lines' => $normalizedLines,
            ...($reversalOf ? ['reversal_of_id' => $reversalOf->id] : []),
        ];
    }

    /** @param list<array<string, mixed>> $lines */
    private function validateTenantReferences(
        Business $business,
        string $entryDate,
        ?Model $source,
        array $lines,
        ?int $reversalOfId = null,
    ): void {
        if ($source && (! $source->exists || ! isset($source->business_id) || $source->business_id !== $business->id)) {
            throw new DomainException('The journal source must belong to the journal business.');
        }

        foreach ($lines as $line) {
            $account = LedgerAccount::withoutGlobalScopes()->find($line['ledger_account_id']);
            $regularAccountUnavailable = ! $reversalOfId && (! $account?->is_active || ! $account?->is_postable);
            if (! $account || $account->business_id !== $business->id || $regularAccountUnavailable) {
                throw new DomainException('Journal lines require active postable accounts from the same business.');
            }

            $this->assertOptionalReference($business, TaxCode::class, $line['tax_code_id'], 'tax code');
            $this->assertOptionalReference($business, Client::class, $line['client_id'], 'client');
            $this->assertOptionalReference($business, Supplier::class, $line['supplier_id'], 'supplier');
            $this->assertOptionalReference($business, Project::class, $line['project_id'], 'project');

            if (! $reversalOfId && $line['tax_code_id'] && ! TaxCode::withoutGlobalScopes()->whereKey($line['tax_code_id'])->effectiveOn($entryDate)->exists()) {
                throw new DomainException('The journal tax code is not effective on the journal date.');
            }
        }
    }

    /** @param list<array<string, mixed>> $lines */
    private function assertReversalPayload(
        Business $business,
        string $entryDate,
        ?Model $source,
        JournalEntry $original,
        array $lines,
    ): void {
        if (! $original->exists || $original->business_id !== $business->id) {
            throw new DomainException('The reversed journal must belong to the journal business.');
        }

        if ($original->status !== JournalEntry::STATUS_POSTED || $original->reversal_of_id !== null) {
            throw new DomainException('Only an original posted journal entry can be reversed.');
        }

        if (! $source instanceof JournalEntry || ! $source->is($original)) {
            throw new DomainException('A reversal journal source must be its original entry.');
        }

        if ($entryDate < $original->entry_date->toDateString()) {
            throw new DomainException('A journal reversal cannot predate the original entry.');
        }

        $expected = $original->lines()->get()->map(fn ($line): array => [
            'ledger_account_id' => $line->ledger_account_id,
            'tax_code_id' => $line->tax_code_id,
            'client_id' => $line->client_id,
            'supplier_id' => $line->supplier_id,
            'project_id' => $line->project_id,
            'debit_gbp' => $line->credit_gbp,
            'credit_gbp' => $line->debit_gbp,
            'original_currency' => $line->original_currency,
            'original_amount' => $line->original_amount,
            'exchange_rate' => $line->exchange_rate,
            'memo' => $line->memo,
            'dimensions' => $line->dimensions,
        ])->all();

        if ($lines !== $expected) {
            throw new DomainException('A reversal must exactly mirror every original journal line.');
        }
    }

    /** @param class-string<Model> $modelClass */
    private function assertOptionalReference(Business $business, string $modelClass, ?int $id, string $label): void
    {
        if ($id === null) {
            return;
        }

        $model = $modelClass::withoutGlobalScopes()->find($id);
        if (! $model || ! isset($model->business_id) || $model->business_id !== $business->id) {
            throw new DomainException("The journal {$label} must belong to the journal business.");
        }
    }

    private function nextEntryNumber(Business $business, string $entryDate): string
    {
        $year = substr($entryDate, 0, 4);
        $prefix = "JNL-{$year}-";
        $last = JournalEntry::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('entry_number', 'like', $prefix.'%')
            ->orderByDesc('entry_number')
            ->value('entry_number');
        $next = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $next, 6, '0', STR_PAD_LEFT);
    }

    private function payloadHash(array $payload): string
    {
        unset($payload['source']);

        try {
            return hash('sha256', json_encode($this->sortRecursively($payload), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION));
        } catch (JsonException) {
            throw new DomainException('The journal snapshot must be JSON serializable.');
        }
    }

    private function sortRecursively(mixed $value): mixed
    {
        if (! is_array($value)) {
            return $value;
        }

        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn (mixed $item): mixed => $this->sortRecursively($item), $value);
    }

    private function moneyToMinorUnits(mixed $value, string $field): int
    {
        $decimal = $this->moneyToDecimal($value, $field);
        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function moneyToDecimal(mixed $value, string $field, bool $allowZero = true): string
    {
        $decimal = trim((string) $value);
        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $decimal)) {
            throw new DomainException("{$field} must be a non-negative amount with at most two decimal places.");
        }

        [$whole, $fraction] = array_pad(explode('.', $decimal, 2), 2, '');
        $normalized = ltrim($whole, '0');
        $normalized = ($normalized === '' ? '0' : $normalized).'.'.str_pad($fraction, 2, '0');

        if (! $allowZero && $normalized === '0.00') {
            throw new DomainException("{$field} must be positive.");
        }

        return $normalized;
    }

    private function positiveDecimal(mixed $value, string $field, int $scale): string
    {
        $decimal = trim((string) $value);
        if (! preg_match('/^\d{1,10}(?:\.\d{1,'.$scale.'})?$/', $decimal) || (float) $decimal <= 0) {
            throw new DomainException("{$field} must be positive with at most {$scale} decimal places.");
        }

        return $decimal;
    }

    private function nullablePositiveInteger(mixed $value, string $field): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $validated = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (! $validated) {
            throw new DomainException("{$field} must be a positive integer.");
        }

        return (int) $validated;
    }

    private function minorUnitsToDecimal(int $minorUnits): string
    {
        return intdiv($minorUnits, 100).'.'.str_pad((string) ($minorUnits % 100), 2, '0', STR_PAD_LEFT);
    }
}
