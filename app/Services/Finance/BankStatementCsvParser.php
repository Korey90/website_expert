<?php

namespace App\Services\Finance;

use App\Data\Finance\BankStatementCsvError;
use App\Data\Finance\BankStatementCsvParseResult;
use App\Data\Finance\BankStatementCsvRow;
use App\Services\Finance\Exceptions\BankStatementCsvImportException;
use DateTimeImmutable;
use DomainException;

class BankStatementCsvParser
{
    private const PROFILE_REVOLUT_PL = 'revolut_pl';

    private const REVOLUT_PL_HEADERS = [
        'Rodzaj',
        'Produkt',
        'Data rozpoczęcia',
        'Data zrealizowania',
        'Opis',
        'Kwota',
        'Opłata',
        'Waluta',
        'State',
        'Saldo',
    ];

    private const REVOLUT_PL_MAPPING = [
        'booking_date' => 'Data zrealizowania',
        'value_date' => null,
        'amount' => 'Kwota',
        'debit' => null,
        'credit' => null,
        'currency' => 'Waluta',
        'reference' => 'Opis',
        'counterparty' => null,
        'provider_id' => null,
    ];

    private const COLUMN_ALIASES = [
        'booking_date' => ['booking_date', 'booking date', 'date', 'transaction date', 'transaction_date'],
        'value_date' => ['value_date', 'value date'],
        'amount' => ['amount', 'transaction amount', 'transaction_amount'],
        'debit' => ['debit', 'money out', 'money_out', 'withdrawal'],
        'credit' => ['credit', 'money in', 'money_in', 'deposit'],
        'currency' => ['currency', 'currency code', 'currency_code'],
        'reference' => ['reference', 'description', 'details', 'memo', 'narrative'],
        'counterparty' => ['counterparty', 'payee', 'payer', 'merchant', 'name'],
        'provider_id' => ['provider_id', 'provider id', 'transaction id', 'transaction_id', 'id'],
    ];

    /**
     * @param  array<string, string|null>  $mapping
     * @param  array<string, mixed>  $options
     */
    public function parse(string $path, array $mapping, array $options = []): BankStatementCsvParseResult
    {
        $contents = $this->readFile($path);
        $fileHash = hash('sha256', $contents);
        $contents = $this->normalizeEncoding($contents);
        $delimiter = $this->resolveDelimiter($contents, (string) ($options['delimiter'] ?? 'auto'));
        $stream = fopen('php://temp', 'w+b');

        if ($stream === false) {
            throw new DomainException('The CSV stream could not be opened.');
        }

        fwrite($stream, $contents);
        rewind($stream);

        try {
            $headers = fgetcsv($stream, separator: $delimiter);

            if ($headers === false) {
                throw new DomainException('The CSV file does not contain a header row.');
            }

            $headers = array_map(fn (mixed $header): string => trim((string) $header), $headers);
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
            $this->validateHeaders($headers);
            $profile = $this->resolveProfile($headers);
            $resolvedMapping = $this->resolveMapping($headers, $mapping, $profile);
            $this->validateMapping($resolvedMapping);
            $options['profile'] = $profile;

            $rows = [];
            $errors = [];
            $rowNumber = 1;
            $totalRows = 0;

            while (($values = fgetcsv($stream, separator: $delimiter)) !== false) {
                $rowNumber++;

                if ($this->isBlankRow($values)) {
                    continue;
                }

                $totalRows++;

                if ($totalRows > (int) config('finance.bank_statement_import.max_rows', 5000)) {
                    throw new DomainException('The CSV file exceeds the configured row limit.');
                }

                if (count($values) !== count($headers)) {
                    $errors[] = new BankStatementCsvError(
                        $rowNumber,
                        ['The row has a different number of columns than the header.'],
                        'column_count_mismatch',
                    );

                    continue;
                }

                /** @var array<string, string|null> $record */
                $record = array_combine($headers, array_map(fn (mixed $value): string => trim((string) $value), $values));

                try {
                    $rows[] = $this->normalizeRow($record, $resolvedMapping, $rowNumber, $options);
                } catch (DomainException $exception) {
                    $errors[] = new BankStatementCsvError(
                        $rowNumber,
                        [$exception->getMessage()],
                        $exception instanceof BankStatementCsvImportException ? $exception->errorCode : 'invalid_row',
                    );
                }
            }

            if ($totalRows === 0) {
                throw new DomainException('The CSV file does not contain any transaction rows.');
            }

            return new BankStatementCsvParseResult(
                $fileHash,
                $headers,
                $rows,
                $errors,
                $totalRows,
                $resolvedMapping,
                $profile,
            );
        } finally {
            fclose($stream);
        }
    }

    /** @return array<int, string> */
    public function headers(string $path, string $delimiterOption = 'auto'): array
    {
        $contents = $this->normalizeEncoding($this->readFile($path));
        $delimiter = $this->resolveDelimiter($contents, $delimiterOption);
        $firstLine = strtok($contents, "\r\n");
        $headers = str_getcsv($firstLine === false ? '' : $firstLine, $delimiter);
        $headers = array_map(fn (mixed $header): string => trim((string) $header), $headers);

        if (isset($headers[0])) {
            $headers[0] = preg_replace('/^\xEF\xBB\xBF/', '', $headers[0]) ?? $headers[0];
        }

        $this->validateHeaders($headers);

        return $headers;
    }

    private function readFile(string $path): string
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new DomainException('The CSV file is missing or cannot be read.');
        }

        $size = filesize($path);

        if ($size === false || $size === 0) {
            throw new DomainException('The CSV file is empty.');
        }

        if ($size > (int) config('finance.bank_statement_import.max_bytes', 5 * 1024 * 1024)) {
            throw new DomainException('The CSV file exceeds the configured size limit.');
        }

        $contents = file_get_contents($path);

        if ($contents === false) {
            throw new DomainException('The CSV file could not be read.');
        }

        return $contents;
    }

    private function normalizeEncoding(string $contents): string
    {
        if (str_starts_with($contents, "\xEF\xBB\xBF")) {
            $contents = substr($contents, 3);
        }

        if (mb_check_encoding($contents, 'UTF-8')) {
            return $contents;
        }

        $converted = mb_convert_encoding($contents, 'UTF-8', 'Windows-1252');

        if (! mb_check_encoding($converted, 'UTF-8')) {
            throw new DomainException('The CSV encoding is not supported. Use UTF-8 or Windows-1252.');
        }

        return $converted;
    }

    private function resolveDelimiter(string $contents, string $option): string
    {
        $known = ['comma' => ',', 'semicolon' => ';', 'tab' => "\t", ',' => ',', ';' => ';', "\t" => "\t"];

        if ($option !== 'auto') {
            if (! isset($known[$option])) {
                throw new DomainException('The selected CSV delimiter is invalid.');
            }

            return $known[$option];
        }

        $firstLine = strtok($contents, "\r\n");
        $firstLine = $firstLine === false ? '' : $firstLine;
        $scores = [];

        foreach ([',', ';', "\t"] as $delimiter) {
            $scores[$delimiter] = count(str_getcsv($firstLine, $delimiter));
        }

        arsort($scores);
        $delimiter = (string) array_key_first($scores);

        if (($scores[$delimiter] ?? 0) < 2) {
            throw new DomainException('The CSV delimiter could not be detected.');
        }

        return $delimiter;
    }

    /** @param array<int, string> $headers */
    private function validateHeaders(array $headers): void
    {
        if ($headers === [] || count($headers) > (int) config('finance.bank_statement_import.max_columns', 100)) {
            throw new DomainException('The CSV header is missing or exceeds the configured column limit.');
        }

        if (in_array('', $headers, true)) {
            throw new DomainException('CSV column names cannot be blank.');
        }

        $normalized = array_map($this->normalizeHeader(...), $headers);

        if (count($normalized) !== count(array_unique($normalized))) {
            throw new DomainException('CSV column names must be unique.');
        }
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<string, string|null>  $mapping
     * @return array<string, string|null>
     */
    private function resolveMapping(array $headers, array $mapping, ?string $profile): array
    {
        $headerLookup = [];

        foreach ($headers as $header) {
            $headerLookup[$this->normalizeHeader($header)] = $header;
        }

        $resolved = [];
        $profileMapping = $profile === self::PROFILE_REVOLUT_PL ? self::REVOLUT_PL_MAPPING : [];

        foreach (self::COLUMN_ALIASES as $target => $aliases) {
            $requested = trim((string) ($mapping[$target] ?? ''));

            if ($requested !== '') {
                $actual = $headerLookup[$this->normalizeHeader($requested)] ?? null;

                if ($actual === null) {
                    throw new DomainException("The mapped CSV column [{$requested}] does not exist.");
                }

                $resolved[$target] = $actual;

                continue;
            }

            $profileColumn = $profileMapping[$target] ?? null;

            if ($profileColumn !== null && isset($headerLookup[$this->normalizeHeader($profileColumn)])) {
                $resolved[$target] = $headerLookup[$this->normalizeHeader($profileColumn)];

                continue;
            }

            $resolved[$target] = null;

            foreach ($aliases as $alias) {
                if (isset($headerLookup[$this->normalizeHeader($alias)])) {
                    $resolved[$target] = $headerLookup[$this->normalizeHeader($alias)];
                    break;
                }
            }
        }

        return $resolved;
    }

    /** @param array<int, string> $headers */
    private function resolveProfile(array $headers): ?string
    {
        $normalizedHeaders = array_map($this->normalizeHeader(...), $headers);
        $normalizedRevolutHeaders = array_map($this->normalizeHeader(...), self::REVOLUT_PL_HEADERS);

        return count(array_diff($normalizedRevolutHeaders, $normalizedHeaders)) === 0
            ? self::PROFILE_REVOLUT_PL
            : null;
    }

    /** @param array<string, string|null> $mapping */
    private function validateMapping(array $mapping): void
    {
        if (empty($mapping['booking_date'])) {
            throw new BankStatementCsvImportException(
                'missing_booking_date',
                'A booking date column must be mapped.',
            );
        }

        if (empty($mapping['amount']) && empty($mapping['debit']) && empty($mapping['credit'])) {
            throw new BankStatementCsvImportException(
                'missing_amount',
                'Map an amount column or at least one debit/credit column.',
            );
        }
    }

    /**
     * @param  array<string, string|null>  $record
     * @param  array<string, string|null>  $mapping
     * @param  array<string, mixed>  $options
     */
    private function normalizeRow(array $record, array $mapping, int $rowNumber, array $options): BankStatementCsvRow
    {
        $profile = is_string($options['profile'] ?? null) ? $options['profile'] : null;
        $decimalSeparator = (string) ($options['decimal_separator'] ?? 'auto');
        $sourceIdentity = $this->profileSourceIdentity($record, $profile, $decimalSeparator);
        $bookingDate = $this->normalizeDate($this->mappedValue($record, $mapping, 'booking_date'), (string) ($options['date_format'] ?? 'auto'), true);
        $valueDate = $this->normalizeDate($this->mappedValue($record, $mapping, 'value_date'), (string) ($options['date_format'] ?? 'auto'), false);
        $amount = $this->normalizeMappedAmount($record, $mapping, $decimalSeparator);
        $currency = strtoupper($this->mappedValue($record, $mapping, 'currency') ?: (string) ($options['default_currency'] ?? 'GBP'));

        if (! array_key_exists($currency, (array) config('currencies.supported', []))) {
            throw new BankStatementCsvImportException(
                'unsupported_currency',
                'The transaction currency is not supported.',
            );
        }

        $accountCurrency = strtoupper(trim((string) ($options['default_currency'] ?? '')));

        if ($accountCurrency !== '' && $currency !== $accountCurrency) {
            throw new BankStatementCsvImportException(
                'currency_mismatch',
                'The transaction currency does not match the selected bank account.',
            );
        }

        $reference = $this->normalizeText($this->mappedValue($record, $mapping, 'reference'), 500, 'reference');
        $counterparty = $this->normalizeText($this->mappedValue($record, $mapping, 'counterparty'), 255, 'counterparty');
        $providerId = $this->normalizeText($this->mappedValue($record, $mapping, 'provider_id'), 191, 'provider ID');

        if ($providerId === null && $profile === self::PROFILE_REVOLUT_PL && $sourceIdentity !== null) {
            $providerId = 'revolut-pl:'.$sourceIdentity;
        }

        $canonical = [
            'booking_date' => $bookingDate,
            'value_date' => $valueDate,
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $this->canonicalText($reference),
            'counterparty' => $this->canonicalText($counterparty),
            'source_identity' => $sourceIdentity,
        ];

        return new BankStatementCsvRow(
            $rowNumber,
            $bookingDate,
            $valueDate,
            $amount,
            $currency,
            $reference,
            $counterparty,
            $providerId,
            hash('sha256', json_encode($canonical, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE)),
        );
    }

    /** @param array<string, string|null> $record @param array<string, string|null> $mapping */
    private function mappedValue(array $record, array $mapping, string $target): string
    {
        $source = $mapping[$target] ?? null;

        return $source === null ? '' : trim((string) ($record[$source] ?? ''));
    }

    private function normalizeDate(string $value, string $formatOption, bool $required): ?string
    {
        if ($value === '') {
            if ($required) {
                throw new BankStatementCsvImportException('booking_date_required', 'Booking date is required.');
            }

            return null;
        }

        $formats = match ($formatOption) {
            'auto' => ['d/m/Y', 'Y-m-d H:i:s', 'Y-m-d', 'd-m-Y', 'd.m.Y', 'm/d/Y'],
            'Y-m-d' => ['Y-m-d', 'Y-m-d H:i:s'],
            'Y-m-d H:i:s', 'd/m/Y', 'd-m-Y', 'd.m.Y', 'm/d/Y' => [$formatOption],
            default => throw new BankStatementCsvImportException('invalid_date_format', 'The selected date format is invalid.'),
        };

        foreach ($formats as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        throw new BankStatementCsvImportException(
            'invalid_date',
            'A transaction date does not match the selected format.',
        );
    }

    /** @param array<string, string|null> $record */
    private function profileSourceIdentity(array $record, ?string $profile, string $decimalSeparator): ?string
    {
        if ($profile !== self::PROFILE_REVOLUT_PL) {
            return null;
        }

        $status = mb_strtoupper(trim((string) ($record['State'] ?? '')));

        if (! in_array($status, ['ZAKOŃCZONO', 'COMPLETED'], true)) {
            throw new BankStatementCsvImportException(
                'unsupported_status',
                'The transaction status is not completed and cannot be imported.',
            );
        }

        $feeValue = trim((string) ($record['Opłata'] ?? ''));
        $fee = $feeValue === '' ? '0.00' : $this->normalizeAmount($feeValue, $decimalSeparator, true);

        if ((float) $fee !== 0.0) {
            throw new BankStatementCsvImportException(
                'unsupported_fee',
                'A non-zero bank fee requires an explicit accounting treatment and cannot be imported yet.',
            );
        }

        $identity = [];

        foreach (self::REVOLUT_PL_HEADERS as $header) {
            $value = trim(preg_replace('/\s+/u', ' ', (string) ($record[$header] ?? '')) ?? (string) ($record[$header] ?? ''));
            $identity[$this->normalizeHeader($header)] = mb_strtolower($value);
        }

        return hash('sha256', json_encode($identity, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /** @param array<string, string|null> $record @param array<string, string|null> $mapping */
    private function normalizeMappedAmount(array $record, array $mapping, string $separator): string
    {
        $amount = $this->mappedValue($record, $mapping, 'amount');

        if ($amount !== '') {
            return $this->normalizeAmount($amount, $separator);
        }

        $debit = $this->mappedValue($record, $mapping, 'debit');
        $credit = $this->mappedValue($record, $mapping, 'credit');

        if ($debit !== '' && $credit !== '') {
            throw new BankStatementCsvImportException(
                'conflicting_amount_columns',
                'A row cannot contain both debit and credit amounts.',
            );
        }

        if ($debit !== '') {
            return number_format(-abs((float) $this->normalizeAmount($debit, $separator)), 2, '.', '');
        }

        if ($credit !== '') {
            return number_format(abs((float) $this->normalizeAmount($credit, $separator)), 2, '.', '');
        }

        throw new BankStatementCsvImportException('amount_required', 'Transaction amount is required.');
    }

    private function normalizeAmount(string $value, string $separator, bool $allowZero = false): string
    {
        $negativeParentheses = str_starts_with(trim($value), '(') && str_ends_with(trim($value), ')');
        $clean = preg_replace('/[^0-9,.+\-]/u', '', $value) ?? '';

        if ($clean === '' || ! preg_match('/^[+\-]?[0-9][0-9,.]*$/', $clean)) {
            throw new BankStatementCsvImportException('invalid_amount', 'A transaction amount is invalid.');
        }

        $decimal = match ($separator) {
            'dot', '.' => '.',
            'comma', ',' => ',',
            'auto' => $this->detectDecimalSeparator($clean),
            default => throw new BankStatementCsvImportException('invalid_decimal_separator', 'The selected decimal separator is invalid.'),
        };

        $thousands = $decimal === '.' ? ',' : '.';
        $normalized = str_replace($thousands, '', $clean);
        $normalized = $decimal === ',' ? str_replace(',', '.', $normalized) : $normalized;

        if (! is_numeric($normalized)) {
            throw new BankStatementCsvImportException('invalid_amount', 'A transaction amount is invalid.');
        }

        $numeric = (float) $normalized;
        $numeric = $negativeParentheses ? -abs($numeric) : $numeric;

        if ((! $allowZero && $numeric == 0.0) || abs($numeric) > 9999999999999.99) {
            throw new BankStatementCsvImportException(
                'amount_out_of_range',
                'A transaction amount must be non-zero and within the supported range.',
            );
        }

        return number_format($numeric, 2, '.', '');
    }

    private function detectDecimalSeparator(string $value): string
    {
        $comma = strrpos($value, ',');
        $dot = strrpos($value, '.');

        if ($comma !== false && $dot !== false) {
            return $comma > $dot ? ',' : '.';
        }

        if ($comma !== false) {
            return preg_match('/,\d{1,2}$/', $value) ? ',' : '.';
        }

        return '.';
    }

    private function normalizeText(string $value, int $maxLength, string $label): ?string
    {
        $value = trim(preg_replace('/\s+/u', ' ', $value) ?? $value);

        if ($value === '') {
            return null;
        }

        if (mb_strlen($value) > $maxLength) {
            throw new DomainException("The {$label} exceeds {$maxLength} characters.");
        }

        return $value;
    }

    private function canonicalText(?string $value): ?string
    {
        return $value === null ? null : mb_strtolower($value);
    }

    /** @param array<int, string|null> $values */
    private function isBlankRow(array $values): bool
    {
        return count(array_filter($values, fn (mixed $value): bool => trim((string) $value) !== '')) === 0;
    }

    private function normalizeHeader(string $header): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $header) ?? $header));
    }
}
