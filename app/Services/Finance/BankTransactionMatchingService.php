<?php

namespace App\Services\Finance;

use App\Data\Finance\BankTransactionMatchCandidate;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
use App\Models\BankTransaction;
use App\Models\ExpensePayment;
use App\Models\Payment;
use App\Models\PurchaseCreditRefund;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;

class BankTransactionMatchingService
{
    /** @return list<BankTransactionMatchCandidate> */
    public function findCandidates(BankTransaction $transaction): array
    {
        $windowDays = (int) config('finance.bank_matching.date_window_days', 30);
        $minimumScore = (int) config('finance.bank_matching.minimum_score', 50);
        $maximumCandidates = (int) config('finance.bank_matching.maximum_candidates', 50);
        $transactionAmount = $this->remainingTransactionAmount($transaction);

        if (abs($transactionAmount) < 0.005) {
            return [];
        }

        $date = $transaction->value_date ?? $transaction->booking_date;
        $dateFrom = $date->copy()->subDays($windowDays)->startOfDay();
        $dateTo = $date->copy()->addDays($windowDays)->endOfDay();
        $rawCandidates = [];

        $payments = Payment::withoutGlobalScopes()
            ->with(['invoice.client'])
            ->where('business_id', $transaction->business_id)
            ->whereIn('status', ['completed', 'refunded'])
            ->where(function ($query) use ($dateFrom, $dateTo): void {
                $query->whereBetween('paid_at', [$dateFrom, $dateTo])
                    ->orWhereBetween('refunded_at', [$dateFrom, $dateTo]);
            })
            ->get();

        foreach ($payments as $payment) {
            if ($payment->paid_at?->betweenIncluded($dateFrom, $dateTo)) {
                $rawCandidates[] = $this->paymentCandidate($payment, false);
            }

            if ($payment->refunded_at?->betweenIncluded($dateFrom, $dateTo)) {
                $rawCandidates[] = $this->paymentCandidate($payment, true);
            }
        }

        $expensePayments = ExpensePayment::withoutGlobalScopes()
            ->with(['expense.supplier'])
            ->where('business_id', $transaction->business_id)
            ->where('settlement_source', ExpensePayment::SOURCE_BANK_ACCOUNT)
            ->where('bank_account_id', $transaction->bank_account_id)
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->get();

        foreach ($expensePayments as $payment) {
            $rawCandidates[] = [
                'key' => 'expense_payment:'.$payment->id,
                'source_type' => ExpensePayment::class,
                'source_id' => $payment->id,
                'event_type' => 'supplier_payment',
                'date' => $payment->paid_at,
                'amount' => $payment->amount,
                'amount_gbp' => $payment->amount_gbp,
                'currency' => $payment->currency,
                'sign' => -1,
                'references' => [$payment->reference, $payment->expense?->supplier_invoice_number],
                'counterparties' => [$payment->expense?->supplier?->name, $payment->expense?->supplier?->trading_name],
            ];
        }

        $purchaseRefunds = PurchaseCreditRefund::withoutGlobalScopes()
            ->with(['expense.supplier', 'purchaseCreditNote'])
            ->where('business_id', $transaction->business_id)
            ->whereBetween('received_at', [$dateFrom->toDateString(), $dateTo->toDateString()])
            ->get();

        foreach ($purchaseRefunds as $refund) {
            $rawCandidates[] = [
                'key' => 'purchase_credit_refund:'.$refund->id,
                'source_type' => PurchaseCreditRefund::class,
                'source_id' => $refund->id,
                'event_type' => 'supplier_refund',
                'date' => $refund->received_at,
                'amount' => $refund->amount,
                'amount_gbp' => $refund->amount_gbp,
                'currency' => $refund->currency,
                'sign' => 1,
                'references' => [$refund->reference, $refund->purchaseCreditNote?->number, $refund->expense?->supplier_invoice_number],
                'counterparties' => [$refund->expense?->supplier?->name, $refund->expense?->supplier?->trading_name],
            ];
        }

        $candidates = [];
        $allocationTotals = $this->sourceAllocationTotals($transaction, $rawCandidates);

        foreach ($rawCandidates as $rawCandidate) {
            $allocationKey = $this->allocationKey(
                $rawCandidate['source_type'],
                $rawCandidate['source_id'],
                $rawCandidate['event_type'],
            );
            $candidate = $this->score(
                $transaction,
                $rawCandidate,
                $transactionAmount,
                $allocationTotals[$allocationKey] ?? 0.0,
            );

            if ($candidate !== null && $candidate->score >= $minimumScore) {
                $candidates[] = $candidate;
            }
        }

        usort($candidates, function (BankTransactionMatchCandidate $left, BankTransactionMatchCandidate $right): int {
            return $right->score <=> $left->score
                ?: (float) $left->amountDifference <=> (float) $right->amountDifference
                ?: $left->dateDifferenceDays <=> $right->dateDifferenceDays
                ?: $left->candidateKey <=> $right->candidateKey;
        });

        return array_slice($candidates, 0, $maximumCandidates);
    }

    /** @return array<string, mixed> */
    private function paymentCandidate(Payment $payment, bool $refund): array
    {
        return [
            'key' => 'payment:'.$payment->id.($refund ? ':refund' : ':receipt'),
            'source_type' => Payment::class,
            'source_id' => $payment->id,
            'event_type' => $refund ? 'customer_refund' : 'customer_payment',
            'date' => $refund ? $payment->refunded_at : $payment->paid_at,
            'amount' => $payment->amount,
            'amount_gbp' => $payment->amount_gbp,
            'currency' => $payment->currency,
            'sign' => $refund ? -1 : 1,
            'references' => [$payment->reference, $payment->invoice?->number],
            'counterparties' => [
                $payment->invoice?->client?->company_name,
                $payment->invoice?->client?->trading_name,
                $payment->invoice?->client?->primary_contact_name,
            ],
        ];
    }

    /**
     * @param  array{key: string, source_type: class-string, source_id: int, event_type: string, date: CarbonInterface, amount: mixed, amount_gbp: mixed, currency: string, sign: int, references: array<int, mixed>, counterparties: array<int, mixed>}  $candidate
     */
    private function score(
        BankTransaction $transaction,
        array $candidate,
        float $transactionAmount,
        float $allocatedOriginalAmount,
    ): ?BankTransactionMatchCandidate {
        $direction = $transactionAmount > 0 ? 'inflow' : 'outflow';

        if (($transactionAmount > 0 ? 1 : -1) !== $candidate['sign']) {
            return null;
        }

        $transactionCurrency = strtoupper($transaction->currency);
        $originalCurrency = strtoupper($candidate['currency']);
        $fullOriginalAmount = abs((float) $candidate['amount']);
        $remainingOriginalAmount = max(0, $fullOriginalAmount - $allocatedOriginalAmount);

        if ($fullOriginalAmount < 0.005 || $remainingOriginalAmount < 0.005) {
            return null;
        }

        $fullComparableAmount = $this->comparableAmount(
            $transactionCurrency,
            $originalCurrency,
            $candidate['amount'],
            $candidate['amount_gbp'],
        );

        if ($fullComparableAmount === null) {
            return null;
        }

        $remainingComparableAmount = round(
            abs($fullComparableAmount) * ($remainingOriginalAmount / $fullOriginalAmount),
            2,
        );
        $signedComparableAmount = $candidate['sign'] * $remainingComparableAmount;
        $transactionPennies = $this->toPennies($transactionAmount);
        $candidatePennies = $this->toPennies($signedComparableAmount);
        [$amountScore, $amountReason] = $this->amountScore(abs($transactionPennies), abs($candidatePennies));
        $transactionDate = $transaction->value_date ?? $transaction->booking_date;
        $dateDifference = (int) $transactionDate->diffInDays($candidate['date'], true);
        [$dateScore, $dateReason] = $this->dateScore($dateDifference);
        [$referenceScore, $referenceReason, $bestReference] = $this->textScore(
            $transaction->reference,
            $candidate['references'],
            20,
            'reference',
        );
        [$counterpartyScore, $counterpartyReason, $bestCounterparty] = $this->textScore(
            $transaction->counterparty,
            $candidate['counterparties'],
            10,
            'counterparty',
        );
        $breakdown = [
            'amount' => $amountScore,
            'date' => $dateScore,
            'reference' => $referenceScore,
            'counterparty' => $counterpartyScore,
        ];
        $score = array_sum($breakdown);
        $reasons = array_values(array_filter([$amountReason, $dateReason, $referenceReason, $counterpartyReason]));

        return new BankTransactionMatchCandidate(
            candidateKey: $candidate['key'],
            sourceType: $candidate['source_type'],
            sourceId: $candidate['source_id'],
            eventType: $candidate['event_type'],
            direction: $direction,
            date: $candidate['date']->toDateString(),
            comparableAmount: number_format($signedComparableAmount, 2, '.', ''),
            comparableCurrency: $transactionCurrency,
            originalAmount: number_format($candidate['sign'] * $remainingOriginalAmount, 2, '.', ''),
            originalCurrency: $originalCurrency,
            amountDifference: number_format(abs($transactionAmount - $signedComparableAmount), 2, '.', ''),
            dateDifferenceDays: $dateDifference,
            reference: $bestReference,
            counterparty: $bestCounterparty,
            score: $score,
            confidence: $this->confidence($score, $amountScore),
            scoreBreakdown: $breakdown,
            reasons: $reasons,
        );
    }

    private function comparableAmount(string $transactionCurrency, string $candidateCurrency, mixed $amount, mixed $amountGbp): ?float
    {
        if ($transactionCurrency === $candidateCurrency) {
            return (float) $amount;
        }

        if ($transactionCurrency === 'GBP' && $amountGbp !== null) {
            return (float) $amountGbp;
        }

        return null;
    }

    private function remainingTransactionAmount(BankTransaction $transaction): float
    {
        $allocated = (float) BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->where('bank_transaction_id', $transaction->id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount');
        $remaining = max(0, abs((float) $transaction->amount) - $allocated);

        return (float) $transaction->amount < 0 ? -$remaining : $remaining;
    }

    /**
     * @param  list<array<string, mixed>>  $candidates
     * @return array<string, float>
     */
    private function sourceAllocationTotals(BankTransaction $transaction, array $candidates): array
    {
        if ($candidates === []) {
            return [];
        }

        $sourceIds = collect($candidates)
            ->groupBy('source_type')
            ->map(fn ($items) => $items->pluck('source_id')->unique()->values()->all());
        $allocations = BankReconciliationAllocation::withoutGlobalScopes()
            ->where('business_id', $transaction->business_id)
            ->whereHas('reconciliation', fn ($query) => $query
                ->withoutGlobalScopes()
                ->where('status', BankReconciliation::STATUS_ACTIVE))
            ->where(function ($query) use ($sourceIds): void {
                foreach ($sourceIds as $sourceType => $ids) {
                    $query->orWhere(function ($query) use ($sourceType, $ids): void {
                        $query->where('reconcilable_type', $sourceType)
                            ->whereIn('reconcilable_id', $ids);
                    });
                }
            })
            ->selectRaw('reconcilable_type, reconcilable_id, event_type, SUM(source_amount) as allocated_source_amount')
            ->groupBy('reconcilable_type', 'reconcilable_id', 'event_type')
            ->get();

        $totals = [];

        foreach ($allocations as $allocation) {
            $totals[$this->allocationKey(
                (string) $allocation->reconcilable_type,
                (int) $allocation->reconcilable_id,
                (string) $allocation->event_type,
            )] = (float) $allocation->allocated_source_amount;
        }

        return $totals;
    }

    private function allocationKey(string $type, int $id, string $eventType): string
    {
        return $type.'|'.$id.'|'.$eventType;
    }

    /** @return array{int, string} */
    private function amountScore(int $transactionPennies, int $candidatePennies): array
    {
        if ($transactionPennies === $candidatePennies) {
            return [50, 'amount_exact'];
        }

        $difference = abs($transactionPennies - $candidatePennies);
        $relativeDifference = $difference / max($transactionPennies, 1);

        if ($relativeDifference <= 0.01) {
            return [45, 'amount_within_1_percent'];
        }

        if ($relativeDifference <= 0.05) {
            return [40, 'amount_within_5_percent'];
        }

        if ($relativeDifference <= 0.10) {
            return [35, 'amount_within_10_percent'];
        }

        $ratio = min($transactionPennies, $candidatePennies) / max($transactionPennies, $candidatePennies, 1);

        return $ratio >= 0.5
            ? [25, 'amount_partial_candidate']
            : [10, 'amount_weak_candidate'];
    }

    /** @return array{int, string} */
    private function dateScore(int $difference): array
    {
        return match (true) {
            $difference === 0 => [20, 'date_same_day'],
            $difference <= 3 => [16, 'date_within_3_days'],
            $difference <= 7 => [12, 'date_within_7_days'],
            $difference <= 14 => [8, 'date_within_14_days'],
            default => [4, 'date_within_window'],
        };
    }

    /**
     * @param  array<int, mixed>  $candidates
     * @return array{int, ?string, ?string}
     */
    private function textScore(?string $needle, array $candidates, int $maximum, string $prefix): array
    {
        $normalizedNeedle = $this->normalizeText($needle);

        if ($normalizedNeedle === '') {
            return [0, null, $this->firstText($candidates)];
        }

        $bestScore = 0;
        $bestReason = null;
        $bestValue = null;

        foreach ($candidates as $candidate) {
            $candidate = is_string($candidate) ? trim($candidate) : '';
            $normalizedCandidate = $this->normalizeText($candidate);

            if ($normalizedCandidate === '') {
                continue;
            }

            if ($normalizedNeedle === $normalizedCandidate) {
                $score = $maximum;
                $reason = $prefix.'_exact';
            } elseif (mb_strlen($normalizedCandidate) >= 4 && (str_contains($normalizedNeedle, $normalizedCandidate) || str_contains($normalizedCandidate, $normalizedNeedle))) {
                $score = (int) round($maximum * 0.8);
                $reason = $prefix.'_contains';
            } else {
                $similarity = $this->tokenSimilarity($normalizedNeedle, $normalizedCandidate);
                $score = match (true) {
                    $similarity >= 0.75 => (int) round($maximum * 0.7),
                    $similarity >= 0.5 => (int) round($maximum * 0.5),
                    $similarity > 0 => (int) round($maximum * 0.25),
                    default => 0,
                };
                $reason = $score > 0 ? $prefix.'_tokens' : null;
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestReason = $reason;
                $bestValue = $candidate;
            }
        }

        return [$bestScore, $bestReason, $bestValue ?? $this->firstText($candidates)];
    }

    private function normalizeText(?string $value): string
    {
        $value = mb_strtolower(Str::ascii((string) $value));

        return trim(preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '');
    }

    private function tokenSimilarity(string $left, string $right): float
    {
        $leftTokens = array_values(array_unique(array_filter(explode(' ', $left))));
        $rightTokens = array_values(array_unique(array_filter(explode(' ', $right))));
        $union = array_unique([...$leftTokens, ...$rightTokens]);

        return $union === [] ? 0.0 : count(array_intersect($leftTokens, $rightTokens)) / count($union);
    }

    /** @param array<int, mixed> $values */
    private function firstText(array $values): ?string
    {
        foreach ($values as $value) {
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return null;
    }

    private function confidence(int $score, int $amountScore): string
    {
        return match (true) {
            $score >= 90 && $amountScore === 50 => 'exact',
            $score >= 75 => 'high',
            $score >= 60 => 'medium',
            default => 'low',
        };
    }

    private function toPennies(float $amount): int
    {
        return (int) round($amount * 100);
    }
}
