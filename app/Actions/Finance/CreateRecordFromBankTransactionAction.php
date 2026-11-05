<?php

namespace App\Actions\Finance;

use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\ExpensePayment;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\TaxCode;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class CreateRecordFromBankTransactionAction
{
    public function __construct(
        private readonly CreateExpenseAction $createExpense,
        private readonly ApproveExpenseAction $approveExpense,
        private readonly PostExpenseAction $postExpense,
        private readonly RecordExpensePaymentAction $recordExpensePayment,
        private readonly RecordInvoicePaymentAction $recordInvoicePayment,
        private readonly ReconcileBankTransactionAction $reconcileTransaction,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function execute(BankTransaction $transaction, User $actor, array $data): BankReconciliation
    {
        $this->authorize($transaction, $actor);

        return DB::transaction(function () use ($transaction, $actor, $data): BankReconciliation {
            Business::query()->whereKey($transaction->business_id)->lockForUpdate()->firstOrFail();

            $locked = BankTransaction::withoutGlobalScopes()
                ->where('business_id', $transaction->business_id)
                ->whereKey($transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($locked->reconciliation_status !== BankTransaction::STATUS_UNMATCHED
                || $locked->reconciliationAllocations()->exists()) {
                throw new DomainException('Only a fully unmatched bank transaction can create a finance record.');
            }

            $bankAmount = abs($this->moneyToPennies($locked->amount));

            if ($bankAmount === 0) {
                throw new DomainException('A zero-value bank transaction cannot create a finance record.');
            }

            $bankAccount = BankAccount::withoutGlobalScopes()->find($locked->bank_account_id);

            if (! $bankAccount
                || $bankAccount->business_id !== $locked->business_id
                || strtoupper($bankAccount->currency) !== strtoupper($locked->currency)) {
                throw new DomainException('The bank transaction requires a matching bank account from the same business and currency.');
            }

            $candidateKey = (float) $locked->amount > 0
                ? $this->createCustomerPayment($locked, $bankAccount, $actor, $data, $bankAmount)
                : $this->createSupplierExpense($locked, $bankAccount, $actor, $data, $bankAmount);

            return $this->reconcileTransaction->execute(
                $locked,
                $actor,
                [[
                    'candidate_key' => $candidateKey,
                    'amount' => $this->penniesToMoney($bankAmount),
                ]],
                $this->text($data['notes'] ?? null, 2000, false),
            );
        }, 3);
    }

    /** @param array<string, mixed> $data */
    private function createCustomerPayment(
        BankTransaction $transaction,
        BankAccount $bankAccount,
        User $actor,
        array $data,
        int $bankAmount,
    ): string {
        $invoice = Invoice::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->find($this->positiveId($data['invoice_id'] ?? null, 'invoice_id'));

        if (! $invoice) {
            throw new DomainException('The selected invoice does not belong to the bank transaction business.');
        }

        $documentCurrency = strtoupper((string) $invoice->currency);
        $bankCurrency = strtoupper((string) $transaction->currency);
        $this->assertSupportedCurrency($documentCurrency);
        $sourceAmount = $this->sourceAmount($bankCurrency, $documentCurrency, $bankAmount, $data['source_amount'] ?? null);
        $settlementRate = $this->settlementRate(
            $bankCurrency,
            $documentCurrency,
            $bankAmount,
            $sourceAmount,
            $data['gbp_exchange_rate'] ?? null,
        );

        $payment = $this->recordInvoicePayment->execute($invoice, $actor, [
            'amount' => $this->penniesToMoney($sourceAmount),
            'method' => 'bank_transfer',
            'bank_account_id' => $bankAccount->id,
            'reference' => "BANK-TXN-{$transaction->id}-RECEIPT",
            'paid_at' => $transaction->booking_date->toDateString(),
            'notes' => $this->text($data['notes'] ?? null, 2000, false),
            'gbp_exchange_rate' => $settlementRate,
            'exchange_rate_source' => $documentCurrency === 'GBP' ? 'identity' : 'bank_transaction',
            'exchange_rate_date' => $transaction->booking_date->toDateString(),
        ]);

        $this->assertPaymentMatchesTransaction($payment, $bankCurrency, $bankAmount);

        return "payment:{$payment->id}:receipt";
    }

    /** @param array<string, mixed> $data */
    private function createSupplierExpense(
        BankTransaction $transaction,
        BankAccount $bankAccount,
        User $actor,
        array $data,
        int $bankAmount,
    ): string {
        $bookingDate = $transaction->booking_date->toDateString();
        $bankCurrency = strtoupper((string) $transaction->currency);
        $documentCurrency = strtoupper(trim((string) ($data['currency'] ?? $bankCurrency)));
        $this->assertSupportedCurrency($documentCurrency);
        $sourceAmount = $this->sourceAmount($bankCurrency, $documentCurrency, $bankAmount, $data['source_amount'] ?? null);
        $settlementRate = $this->settlementRate(
            $bankCurrency,
            $documentCurrency,
            $bankAmount,
            $sourceAmount,
            $data['gbp_exchange_rate'] ?? null,
        );
        $taxCode = TaxCode::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->effectiveOn($bookingDate)
            ->find($this->positiveId($data['tax_code_id'] ?? null, 'tax_code_id'));

        if (! $taxCode) {
            throw new DomainException('The tax code is not effective for this business on the transaction date.');
        }

        $ledgerAccountId = $this->positiveId($data['ledger_account_id'] ?? null, 'ledger_account_id');
        $ledgerAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->whereKey($ledgerAccountId)
            ->activePostable()
            ->where('type', 'expense')
            ->first();

        if (! $ledgerAccount) {
            throw new DomainException('The expense ledger account is unavailable for this business.');
        }

        $category = trim((string) ($data['category'] ?? 'other'));

        if (! in_array($category, config('finance.expense_categories', []), true)) {
            throw new DomainException('The expense category is invalid.');
        }

        $expense = $this->createExpense->execute(
            Business::query()->findOrFail($transaction->business_id),
            $actor,
            [
                'supplier_id' => $data['supplier_id'] ?? null,
                'supplier_invoice_number' => $this->text($data['supplier_invoice_number'] ?? null, 255, false),
                'currency' => $documentCurrency,
                'gbp_exchange_rate' => $settlementRate,
                'exchange_rate_source' => $documentCurrency === 'GBP' ? 'identity' : 'bank_transaction',
                'exchange_rate_date' => $bookingDate,
                'issue_date' => $bookingDate,
                'tax_point' => $bookingDate,
                'due_date' => $bookingDate,
                'received_date' => $bookingDate,
                'payment_method' => 'bank_transfer',
                'business_purpose' => $this->text($data['business_purpose'] ?? null, 2000),
                'notes' => $this->text($data['notes'] ?? null, 5000, false),
                'items' => [[
                    'description' => $this->text($data['description'] ?? null, 255),
                    'category' => $category,
                    'quantity' => 1,
                    'unit_price' => $this->penniesToMoney($this->grossToNet($sourceAmount, $taxCode)),
                    'tax_code_id' => $taxCode->id,
                    'ledger_account_id' => $ledgerAccount->id,
                    'supply_date' => $bookingDate,
                ]],
            ],
        );

        if ($this->moneyToPennies($expense->total) !== $sourceAmount) {
            throw new DomainException('The expense tax calculation does not equal the bank transaction source amount.');
        }

        $expense = $this->approveExpense->execute($expense, $actor);
        $expense = $this->postExpense->execute($expense, $actor);
        $payment = $this->recordExpensePayment->execute($expense, $actor, [
            'amount' => $this->penniesToMoney($sourceAmount),
            'method' => 'bank_transfer',
            'bank_account_id' => $bankAccount->id,
            'reference' => "BANK-TXN-{$transaction->id}-EXPENSE",
            'paid_at' => $bookingDate,
            'notes' => $this->text($data['notes'] ?? null, 2000, false),
            'gbp_exchange_rate' => $settlementRate,
            'exchange_rate_source' => $documentCurrency === 'GBP' ? 'identity' : 'bank_transaction',
            'exchange_rate_date' => $bookingDate,
        ]);

        $this->assertExpensePaymentMatchesTransaction($payment, $bankCurrency, $bankAmount);

        return "expense_payment:{$payment->id}";
    }

    private function authorize(BankTransaction $transaction, User $actor): void
    {
        if (! $actor->businessMemberships()
            ->where('business_id', $transaction->business_id)
            ->where('is_active', true)
            ->exists()) {
            throw new DomainException('The actor does not belong to the bank transaction business.');
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')
            || ! PermissionHelper::allows($actor, 'post_finance')) {
            throw new DomainException('The actor cannot create and post finance records from bank transactions.');
        }
    }

    private function sourceAmount(string $bankCurrency, string $documentCurrency, int $bankAmount, mixed $requested): int
    {
        if ($documentCurrency === $bankCurrency) {
            if (filled($requested) && $this->positiveMoneyToPennies($requested, 'source_amount') !== $bankAmount) {
                throw new DomainException('The source amount must equal the bank amount when currencies match.');
            }

            return $bankAmount;
        }

        if ($bankCurrency !== 'GBP' || $documentCurrency === 'GBP') {
            throw new DomainException('The document currency is incompatible with the bank transaction currency.');
        }

        return $this->positiveMoneyToPennies($requested, 'source_amount');
    }

    private function settlementRate(
        string $bankCurrency,
        string $documentCurrency,
        int $bankAmount,
        int $sourceAmount,
        mixed $requested,
    ): string {
        if ($documentCurrency === 'GBP') {
            return '1.00000000';
        }

        if ($bankCurrency === 'GBP') {
            return number_format($bankAmount / $sourceAmount, 8, '.', '');
        }

        $rate = trim((string) $requested);

        if (! preg_match('/^\d{1,10}(?:\.\d{1,8})?$/', $rate) || (float) $rate <= 0) {
            throw new DomainException('A positive GBP exchange rate with at most eight decimals is required.');
        }

        return number_format((float) $rate, 8, '.', '');
    }

    private function grossToNet(int $grossAmount, TaxCode $taxCode): int
    {
        if ($taxCode->type === 'reverse_charge') {
            return $grossAmount;
        }

        $rateBasisPoints = (int) round((float) $taxCode->rate * 100);

        if ($rateBasisPoints === 0) {
            return $grossAmount;
        }

        $estimated = (int) round($grossAmount * 10000 / (10000 + $rateBasisPoints));

        for ($net = max(1, $estimated - 3); $net <= $estimated + 3; $net++) {
            $vat = (int) round($net * $rateBasisPoints / 10000);

            if ($net + $vat === $grossAmount) {
                return $net;
            }
        }

        throw new DomainException('The gross bank amount cannot be represented exactly with the selected tax rate.');
    }

    private function assertPaymentMatchesTransaction(Payment $payment, string $bankCurrency, int $bankAmount): void
    {
        $comparable = $bankCurrency === 'GBP'
            ? $this->moneyToPennies($payment->amount_gbp)
            : $this->moneyToPennies($payment->amount);

        if ($comparable !== $bankAmount) {
            throw new DomainException('The customer payment snapshot does not equal the bank transaction amount.');
        }
    }

    private function assertExpensePaymentMatchesTransaction(ExpensePayment $payment, string $bankCurrency, int $bankAmount): void
    {
        $comparable = $bankCurrency === 'GBP'
            ? $this->moneyToPennies($payment->amount_gbp)
            : $this->moneyToPennies($payment->amount);

        if ($comparable !== $bankAmount) {
            throw new DomainException('The supplier payment snapshot does not equal the bank transaction amount.');
        }
    }

    private function positiveId(mixed $value, string $field): int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        if (! $id) {
            throw new DomainException("{$field} must be a positive integer.");
        }

        return (int) $id;
    }

    private function assertSupportedCurrency(string $currency): void
    {
        if (! array_key_exists($currency, config('currencies.supported', []))) {
            throw new DomainException('The document currency is not supported.');
        }
    }

    private function text(mixed $value, int $maxLength, bool $required = true): ?string
    {
        $text = trim((string) $value);

        if (($required && $text === '') || mb_strlen($text) > $maxLength) {
            throw new DomainException("Text values must contain between 1 and {$maxLength} characters.");
        }

        return $text !== '' ? $text : null;
    }

    private function positiveMoneyToPennies(mixed $value, string $field): int
    {
        $amount = trim((string) $value);

        if (! preg_match('/^\d{1,13}(?:\.\d{1,2})?$/', $amount)) {
            throw new DomainException("{$field} must be a positive amount with at most two decimals.");
        }

        $pennies = $this->moneyToPennies($amount);

        if ($pennies <= 0) {
            throw new DomainException("{$field} must be greater than zero.");
        }

        return $pennies;
    }

    private function moneyToPennies(mixed $value): int
    {
        return (int) round((float) $value * 100);
    }

    private function penniesToMoney(int $value): string
    {
        return number_format($value / 100, 2, '.', '');
    }
}
