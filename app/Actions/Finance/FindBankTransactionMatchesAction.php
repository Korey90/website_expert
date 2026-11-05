<?php

namespace App\Actions\Finance;

use App\Data\Finance\BankTransactionMatchCandidate;
use App\Models\BankTransaction;
use App\Models\User;
use App\Services\Finance\BankTransactionMatchingService;
use App\Support\PermissionHelper;
use DomainException;

class FindBankTransactionMatchesAction
{
    public function __construct(private readonly BankTransactionMatchingService $matchingService) {}

    /** @return list<BankTransactionMatchCandidate> */
    public function execute(BankTransaction $transaction, User $actor): array
    {
        if (! $actor->businessMemberships()
            ->where('business_id', $transaction->business_id)
            ->where('is_active', true)
            ->exists()) {
            throw new DomainException('The actor does not belong to the bank transaction business.');
        }

        if (! PermissionHelper::allows($actor, 'view_finance')) {
            throw new DomainException('The actor cannot view bank transaction matches.');
        }

        if ($transaction->reconciliation_status !== BankTransaction::STATUS_UNMATCHED) {
            throw new DomainException('Only unmatched bank transactions can be scored.');
        }

        return $this->matchingService->findCandidates($transaction);
    }
}
