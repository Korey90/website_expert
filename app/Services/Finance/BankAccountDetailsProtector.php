<?php

namespace App\Services\Finance;

use DomainException;
use RuntimeException;

class BankAccountDetailsProtector
{
    /** @return array{account_number_last4: string, sort_code_last2: string, account_fingerprint: string} */
    public function protect(string $accountNumber, string $sortCode): array
    {
        $accountNumber = $this->digits($accountNumber);
        $sortCode = $this->digits($sortCode);

        if (strlen($accountNumber) !== 8) {
            throw new DomainException('A UK bank account number must contain exactly 8 digits.');
        }

        if (strlen($sortCode) !== 6) {
            throw new DomainException('A UK sort code must contain exactly 6 digits.');
        }

        $key = (string) config('finance.bank_account_fingerprint_key');

        if (blank($key)) {
            throw new RuntimeException('The bank account fingerprint key is not configured.');
        }

        return [
            'account_number_last4' => substr($accountNumber, -4),
            'sort_code_last2' => substr($sortCode, -2),
            'account_fingerprint' => hash_hmac('sha256', "uk:{$sortCode}:{$accountNumber}", $key),
        ];
    }

    private function digits(string $value): string
    {
        return preg_replace('/\D+/', '', trim($value)) ?? '';
    }
}
