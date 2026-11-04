<?php

namespace App\Actions\Finance;

use App\Models\AccountingPeriod;
use App\Models\User;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class TransitionAccountingPeriodAction
{
    /** @var array<string, list<string>> */
    private const TRANSITIONS = [
        AccountingPeriod::STATUS_OPEN => [AccountingPeriod::STATUS_CLOSING],
        AccountingPeriod::STATUS_CLOSING => [AccountingPeriod::STATUS_OPEN, AccountingPeriod::STATUS_CLOSED],
        AccountingPeriod::STATUS_CLOSED => [AccountingPeriod::STATUS_OPEN, AccountingPeriod::STATUS_LOCKED],
        AccountingPeriod::STATUS_LOCKED => [],
    ];

    public function execute(AccountingPeriod $period, User $actor, string $targetStatus, ?string $reason = null): AccountingPeriod
    {
        return DB::transaction(function () use ($period, $actor, $targetStatus, $reason): AccountingPeriod {
            $locked = AccountingPeriod::withoutGlobalScopes()->lockForUpdate()->findOrFail($period->id);
            $this->assertAuthorized($locked, $actor);

            if (! in_array($targetStatus, self::TRANSITIONS[$locked->status] ?? [], true)) {
                throw new DomainException("Accounting period cannot transition from {$locked->status} to {$targetStatus}.");
            }

            if (in_array($targetStatus, [AccountingPeriod::STATUS_OPEN, AccountingPeriod::STATUS_LOCKED], true) && blank($reason)) {
                throw new DomainException('A reason is required to reopen or lock an accounting period.');
            }

            $now = now();
            $history = $locked->transition_history ?? [];
            $history[] = [
                'from' => $locked->status,
                'to' => $targetStatus,
                'at' => $now->toIso8601String(),
                'by' => $actor->id,
                'reason' => $reason,
            ];
            $metadata = match ($targetStatus) {
                AccountingPeriod::STATUS_CLOSING => ['closing_started_by' => $actor->id, 'closing_started_at' => $now],
                AccountingPeriod::STATUS_CLOSED => ['closed_by' => $actor->id, 'closed_at' => $now, 'close_notes' => $reason],
                AccountingPeriod::STATUS_LOCKED => ['locked_by' => $actor->id, 'locked_at' => $now, 'lock_reason' => $reason],
                AccountingPeriod::STATUS_OPEN => ['reopened_by' => $actor->id, 'reopened_at' => $now, 'reopen_reason' => $reason],
            };
            $locked->update([
                'status' => $targetStatus,
                'transition_history' => $history,
                ...$metadata,
            ]);

            return $locked->fresh();
        });
    }

    private function assertAuthorized(AccountingPeriod $period, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $period->business_id)->where('is_active', true)->exists()) {
            throw new DomainException('The actor does not belong to the accounting period business.');
        }

        if (! PermissionHelper::allows($actor, 'close_finance_periods')) {
            throw new DomainException('The actor cannot close or lock accounting periods.');
        }
    }
}
