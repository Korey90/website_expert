<?php

namespace App\Services\Finance;

use App\Models\BankAccount;
use App\Models\Business;
use App\Models\LedgerAccount;
use App\Services\Currency\CurrencyResolver;
use Carbon\CarbonImmutable;
use DomainException;

class BankAccountDataNormalizer
{
    public function __construct(
        private readonly BankAccountDetailsProtector $protector,
        private readonly CurrencyResolver $currencies,
    ) {}

    /** @param array<string, mixed> $data */
    public function normalize(Business $business, array $data, ?BankAccount $existing = null): array
    {
        $name = trim((string) ($data['name'] ?? $existing?->name ?? ''));
        $bankName = trim((string) ($data['bank_name'] ?? $existing?->bank_name ?? ''));

        if ($name === '' || mb_strlen($name) > 255 || $bankName === '' || mb_strlen($bankName) > 255) {
            throw new DomainException('A bank account requires a name and bank name of at most 255 characters.');
        }

        $ledgerAccountId = filter_var(
            $data['ledger_account_id'] ?? $existing?->ledger_account_id,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]],
        );
        $ledgerAccount = $ledgerAccountId
            ? LedgerAccount::withoutGlobalScopes()->find($ledgerAccountId)
            : null;

        if (! $ledgerAccount
            || $ledgerAccount->business_id !== $business->id
            || $ledgerAccount->type !== 'asset'
            || $ledgerAccount->subtype !== 'cash'
            || ! $ledgerAccount->is_postable
            || ! $ledgerAccount->is_active) {
            throw new DomainException('A bank account requires an active cash ledger account from the same business.');
        }

        $currency = strtoupper(trim((string) ($data['currency'] ?? $existing?->currency ?? 'GBP')));

        if (! $this->currencies->isSupported($currency)) {
            throw new DomainException('The bank account currency is not supported.');
        }

        $openingBalance = $data['opening_balance'] ?? $existing?->opening_balance ?? 0;

        if (! is_numeric($openingBalance) || abs((float) $openingBalance) > 9999999999999.99) {
            throw new DomainException('The bank account opening balance is invalid.');
        }

        $openingBalanceDate = $data['opening_balance_date'] ?? $existing?->opening_balance_date?->toDateString();

        if (filled($openingBalanceDate)) {
            try {
                $date = CarbonImmutable::createFromFormat('!Y-m-d', (string) $openingBalanceDate);
            } catch (\Throwable) {
                $date = null;
            }

            if (! $date || $date->format('Y-m-d') !== (string) $openingBalanceDate) {
                throw new DomainException('The opening balance date must use a valid YYYY-MM-DD value.');
            }
        }

        $status = (string) ($data['status'] ?? $existing?->status ?? BankAccount::STATUS_ACTIVE);

        if (! in_array($status, [BankAccount::STATUS_ACTIVE, BankAccount::STATUS_ARCHIVED], true)) {
            throw new DomainException('The bank account status is invalid.');
        }

        $accountNumber = trim((string) ($data['account_number'] ?? ''));
        $sortCode = trim((string) ($data['sort_code'] ?? ''));

        if (($accountNumber === '') xor ($sortCode === '')) {
            throw new DomainException('Account number and sort code must be supplied together.');
        }

        if (! $existing && $accountNumber === '') {
            throw new DomainException('Account number and sort code are required.');
        }

        $protected = $accountNumber !== ''
            ? $this->protector->protect($accountNumber, $sortCode)
            : [
                'account_number_last4' => $existing->account_number_last4,
                'sort_code_last2' => $existing->sort_code_last2,
                'account_fingerprint' => $existing->account_fingerprint,
            ];

        $duplicate = BankAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('account_fingerprint', $protected['account_fingerprint'])
            ->when($existing, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists();

        if ($duplicate) {
            throw new DomainException('This bank account is already registered for the business.');
        }

        return [
            'business_id' => $business->id,
            'ledger_account_id' => $ledgerAccount->id,
            'name' => $name,
            'bank_name' => $bankName,
            ...$protected,
            'currency' => $currency,
            'opening_balance' => number_format((float) $openingBalance, 2, '.', ''),
            'opening_balance_date' => filled($openingBalanceDate) ? $openingBalanceDate : null,
            'status' => $status,
        ];
    }
}
