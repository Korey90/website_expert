<?php

namespace App\Actions\Finance;

use App\Data\Finance\BankStatementCsvError;
use App\Data\Finance\BankStatementCsvRow;
use App\Data\Finance\BankStatementImportResult;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\User;
use App\Services\Finance\BankStatementCsvParser;
use App\Services\Finance\Exceptions\BankStatementCsvImportException;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportBankStatementCsvAction
{
    public function __construct(private readonly BankStatementCsvParser $parser) {}

    /**
     * @param  array<string, string|null>  $mapping
     * @param  array<string, mixed>  $options
     */
    public function execute(BankAccount $account, User $actor, string $path, array $mapping, array $options = []): BankStatementImportResult
    {
        $this->assertAuthorized($account, $actor);

        if ($account->status !== BankAccount::STATUS_ACTIVE) {
            throw new DomainException('Bank statements can only be imported into an active bank account.');
        }

        $options = [
            'date_format' => $options['date_format'] ?? 'auto',
            'decimal_separator' => $options['decimal_separator'] ?? 'auto',
            'delimiter' => $options['delimiter'] ?? 'auto',
            'default_currency' => $account->currency,
            'dry_run' => (bool) ($options['dry_run'] ?? true),
            'original_filename' => $this->safeFilename((string) ($options['original_filename'] ?? basename($path))),
        ];
        $parsed = $this->parser->parse($path, $mapping, $options);

        if ($parsed->validRows() === 0) {
            $errorCodes = array_values(array_unique(array_map(
                fn (BankStatementCsvError $error): string => $error->code,
                $parsed->errors,
            )));
            $errorCode = count($errorCodes) === 1 ? $errorCodes[0] : 'no_valid_rows';

            throw new BankStatementCsvImportException(
                $errorCode,
                'The CSV file does not contain any valid transaction rows.',
            );
        }

        [$uniqueRows, $knownDuplicates] = $this->uniqueRows($account, $parsed->rows);

        if ($options['dry_run']) {
            return new BankStatementImportResult(true, $parsed, 0, $knownDuplicates);
        }

        return DB::transaction(function () use ($account, $actor, $options, $parsed, $uniqueRows): BankStatementImportResult {
            $lockedAccount = BankAccount::withoutGlobalScopes()->lockForUpdate()->findOrFail($account->id);
            $this->assertAuthorized($lockedAccount, $actor);

            if ($lockedAccount->status !== BankAccount::STATUS_ACTIVE) {
                throw new DomainException('Bank statements can only be imported into an active bank account.');
            }

            $import = BankStatementImport::withoutGlobalScopes()->create([
                'business_id' => $lockedAccount->business_id,
                'bank_account_id' => $lockedAccount->id,
                'original_filename' => $options['original_filename'],
                'file_hash' => $parsed->fileHash,
                'status' => $parsed->failedRows() > 0
                    ? BankStatementImport::STATUS_COMPLETED_WITH_ERRORS
                    : BankStatementImport::STATUS_COMPLETED,
                'total_rows' => $parsed->totalRows,
                'valid_rows' => $parsed->validRows(),
                'imported_rows' => 0,
                'duplicate_rows' => 0,
                'failed_rows' => $parsed->failedRows(),
                'column_mapping' => array_filter($parsed->resolvedMapping, fn (mixed $value): bool => filled($value)),
                'options' => [
                    'date_format' => $options['date_format'],
                    'decimal_separator' => $options['decimal_separator'],
                    'delimiter' => $options['delimiter'],
                    'profile' => $parsed->profile,
                ],
                'imported_by' => $actor->id,
                'completed_at' => now(),
            ]);

            $now = now();
            $insertRows = array_map(fn (BankStatementCsvRow $row): array => [
                'business_id' => $lockedAccount->business_id,
                'bank_account_id' => $lockedAccount->id,
                'bank_statement_import_id' => $import->id,
                'provider_id' => $row->providerId,
                'booking_date' => $row->bookingDate,
                'value_date' => $row->valueDate,
                'amount' => $row->amount,
                'currency' => $row->currency,
                'reference' => $row->reference,
                'counterparty' => $row->counterparty,
                'source_hash' => $row->sourceHash,
                'import_row_number' => $row->rowNumber,
                'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
                'created_at' => $now,
                'updated_at' => $now,
            ], $uniqueRows);
            $importedRows = 0;

            foreach (array_chunk($insertRows, 500) as $chunk) {
                $importedRows += DB::table('bank_transactions')->insertOrIgnore($chunk);
            }

            $duplicateRows = $parsed->validRows() - $importedRows;
            $import->update([
                'imported_rows' => $importedRows,
                'duplicate_rows' => $duplicateRows,
            ]);
            $lockedAccount->forceFill(['last_synced_at' => $now])->save();

            return new BankStatementImportResult(false, $parsed, $importedRows, $duplicateRows, $import->id);
        });
    }

    /**
     * @param  array<int, BankStatementCsvRow>  $rows
     * @return array{0: array<int, BankStatementCsvRow>, 1: int}
     */
    private function uniqueRows(BankAccount $account, array $rows): array
    {
        $hashes = array_values(array_unique(array_map(fn (BankStatementCsvRow $row): string => $row->sourceHash, $rows)));
        $providerIds = array_values(array_unique(array_filter(array_map(fn (BankStatementCsvRow $row): ?string => $row->providerId, $rows))));
        $existingHashes = [];
        $existingProviders = [];

        foreach (array_chunk($hashes, 500) as $chunk) {
            $found = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $account->business_id)
                ->where('bank_account_id', $account->id)
                ->whereIn('source_hash', $chunk)
                ->pluck('source_hash')
                ->all();
            $existingHashes += array_fill_keys($found, true);
        }

        foreach (array_chunk($providerIds, 500) as $chunk) {
            $found = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $account->business_id)
                ->where('bank_account_id', $account->id)
                ->whereIn('provider_id', $chunk)
                ->pluck('provider_id')
                ->all();
            $existingProviders += array_fill_keys($found, true);
        }

        $unique = [];
        $duplicates = 0;

        foreach ($rows as $row) {
            if (isset($existingHashes[$row->sourceHash]) || ($row->providerId !== null && isset($existingProviders[$row->providerId]))) {
                $duplicates++;

                continue;
            }

            $unique[] = $row;
            $existingHashes[$row->sourceHash] = true;

            if ($row->providerId !== null) {
                $existingProviders[$row->providerId] = true;
            }
        }

        return [$unique, $duplicates];
    }

    private function assertAuthorized(BankAccount $account, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $account->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the bank account business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException('The actor cannot import bank statements.');
        }
    }

    private function safeFilename(string $filename): string
    {
        $filename = basename(str_replace('\\', '/', $filename));
        $filename = preg_replace('/[^\pL\pN._ -]/u', '_', $filename) ?? 'statement.csv';

        return Str::limit($filename !== '' ? $filename : 'statement.csv', 255, '');
    }
}
