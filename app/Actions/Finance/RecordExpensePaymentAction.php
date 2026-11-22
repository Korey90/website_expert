<?php

namespace App\Actions\Finance;

use App\Models\AccountingProfile;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\Expense;
use App\Models\ExpensePayment;
use App\Models\User;
use App\Services\Finance\RealisedFxCalculator;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordExpensePaymentAction
{
    public function __construct(
        private readonly PostExpensePaymentAction $postExpensePayment,
        private readonly RealisedFxCalculator $fxCalculator,
    ) {}

    /** @param array{amount: float|int|string, method: string, settlement_source?: string|null, bank_account_id?: int|string|null, reference?: string|null, paid_at?: string|null, notes?: string|null, gbp_exchange_rate?: float|int|string|null, exchange_rate_source?: string|null, exchange_rate_date?: string|null} $data */
    public function execute(Expense $expense, User $actor, array $data): ExpensePayment
    {
        return DB::transaction(function () use ($expense, $actor, $data): ExpensePayment {
            Business::query()->lockForUpdate()->findOrFail($expense->business_id);
            $locked = Expense::withoutGlobalScopes()->lockForUpdate()->findOrFail($expense->id);
            $this->assertActorMembership($actor, $locked->business_id);
            $reference = trim((string) ($data['reference'] ?? '')) ?: 'EPAY-'.Str::uuid();
            $amount = round((float) ($data['amount'] ?? 0), 2);
            $settlementSource = $this->settlementSource($data);
            $bankAccountId = $this->bankAccountId(
                $locked->business_id,
                $settlementSource,
                $data['bank_account_id'] ?? null,
            );
            $this->assertSettlementSourceIsAllowed($locked->business_id, $settlementSource);

            $existing = ExpensePayment::withoutGlobalScopes()
                ->where('business_id', $locked->business_id)
                ->where('reference', $reference)
                ->first();

            if ($existing) {
                $requestedRate = filled($data['gbp_exchange_rate'] ?? null)
                    ? round((float) $data['gbp_exchange_rate'], 8)
                    : null;

                if ($existing->expense_id !== $locked->id
                    || round((float) $existing->amount, 2) !== $amount
                    || $existing->settlement_source !== $settlementSource
                    || $existing->bank_account_id !== $bankAccountId
                    || $existing->method !== (string) ($data['method'] ?? '')
                    || ($requestedRate !== null && round((float) $existing->gbp_exchange_rate, 8) !== $requestedRate)) {
                    throw new DomainException('The supplier payment reference is already used for different payment data.');
                }

                $this->postExpensePayment->execute($existing, $actor);

                return $existing->fresh(['expense', 'journalEntries']);
            }

            if (! in_array($locked->status, [Expense::STATUS_POSTED, Expense::STATUS_PARTIALLY_PAID], true)
                || ! $locked->journalEntries()->exists()) {
                throw new DomainException('Only a posted outstanding expense can receive a payment.');
            }

            if ($amount <= 0 || $amount > round((float) $locked->amount_due, 2)) {
                throw new DomainException('Payment exceeds the outstanding expense balance.');
            }

            if (blank($data['method'] ?? null)) {
                throw new DomainException('A supplier payment method is required.');
            }

            $currency = strtoupper((string) $locked->currency);
            $carryingRate = $currency === 'GBP' ? 1.0 : (float) $locked->gbp_exchange_rate;
            $providedRate = filled($data['gbp_exchange_rate'] ?? null);
            $settlementRate = $currency === 'GBP'
                ? 1.0
                : (float) ($providedRate ? $data['gbp_exchange_rate'] : $locked->gbp_exchange_rate);
            if ($carryingRate <= 0 || $settlementRate <= 0) {
                throw new DomainException('The expense and payment require positive GBP exchange-rate snapshots.');
            }
            $fx = $this->fxCalculator->calculate(
                $amount,
                $settlementRate,
                $carryingRate,
                RealisedFxCalculator::OUTGOING,
            );

            $payment = ExpensePayment::withoutGlobalScopes()->create([
                ...Arr::only($data, ['method', 'notes']),
                ...$fx,
                'business_id' => $locked->business_id,
                'expense_id' => $locked->id,
                'bank_account_id' => $bankAccountId,
                'settlement_source' => $settlementSource,
                'created_by' => $actor->id,
                'amount' => $amount,
                'currency' => $currency,
                'gbp_exchange_rate' => $settlementRate,
                'exchange_rate_source' => $currency === 'GBP'
                    ? 'identity'
                    : ($providedRate ? ($data['exchange_rate_source'] ?? 'manual') : ($locked->exchange_rate_source ?: 'expense_snapshot')),
                'exchange_rate_date' => $currency === 'GBP'
                    ? ($data['paid_at'] ?? now())
                    : ($providedRate
                        ? ($data['exchange_rate_date'] ?? $data['paid_at'] ?? now())
                        : ($locked->exchange_rate_date ?? $locked->tax_point ?? $locked->issue_date)),
                'reference' => $reference,
                'paid_at' => $data['paid_at'] ?? now(),
            ]);

            $newPaid = round((float) $locked->amount_paid + $amount, 2);
            $newDue = round((float) $locked->amount_due - $amount, 2);
            $locked->update([
                'amount_paid' => $newPaid,
                'amount_due' => $newDue,
                'status' => $newDue === 0.0 ? Expense::STATUS_PAID : Expense::STATUS_PARTIALLY_PAID,
            ]);

            $this->postExpensePayment->execute($payment, $actor);

            return $payment->fresh(['expense', 'journalEntries']);
        }, 3);
    }

    private function assertActorMembership(User $actor, string $businessId): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the expense business.');
        }

        if (! PermissionHelper::allows($actor, 'post_finance')) {
            throw new DomainException('The actor cannot post supplier payments.');
        }
    }

    /** @param array<string, mixed> $data */
    private function settlementSource(array $data): string
    {
        $source = trim((string) ($data['settlement_source'] ?? ''));

        if ($source === '') {
            $source = filled($data['bank_account_id'] ?? null)
                ? ExpensePayment::SOURCE_BANK_ACCOUNT
                : ExpensePayment::SOURCE_CASH_ACCOUNT;
        }

        if (! in_array($source, ExpensePayment::settlementSources(), true)) {
            throw new DomainException('The supplier payment settlement source is invalid.');
        }

        return $source;
    }

    private function bankAccountId(string $businessId, string $source, mixed $bankAccountId): ?int
    {
        if ($source !== ExpensePayment::SOURCE_BANK_ACCOUNT) {
            if (filled($bankAccountId)) {
                throw new DomainException('Only a business bank settlement may specify a bank account.');
            }

            return null;
        }

        if (blank($bankAccountId)) {
            throw new DomainException('A business bank account is required for a bank settlement.');
        }

        $account = BankAccount::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->active()
            ->find($bankAccountId);

        if (! $account) {
            throw new DomainException('The supplier payment requires an active bank account from the same business.');
        }

        return (int) $account->id;
    }

    private function assertSettlementSourceIsAllowed(string $businessId, string $source): void
    {
        if ($source !== ExpensePayment::SOURCE_OWNER_FUNDS) {
            return;
        }

        $entityType = AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $businessId)
            ->value('entity_type');

        if ($entityType !== 'sole_trader') {
            throw new DomainException('Owner-funded supplier payments are available only to sole traders.');
        }
    }
}
