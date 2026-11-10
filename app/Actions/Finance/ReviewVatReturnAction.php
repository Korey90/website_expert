<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Support\Facades\DB;

class ReviewVatReturnAction
{
    public function __construct(private readonly VatReturnSnapshotService $snapshotService) {}

    public function execute(VatReturn $return, User $actor, bool $confirmed, ?string $notes = null): VatReturn
    {
        return DB::transaction(function () use ($return, $actor, $confirmed, $notes): VatReturn {
            Business::query()->lockForUpdate()->findOrFail($return->business_id);
            $locked = VatReturn::withoutGlobalScopes()->lockForUpdate()->findOrFail($return->id);
            $this->assertAuthorized($locked, $actor);

            if (! $confirmed) {
                throw new DomainException(__('finance.vat_returns.errors.confirmation_required'));
            }

            $notes = trim((string) $notes);
            if (mb_strlen($notes) > 2000) {
                throw new DomainException(__('finance.vat_returns.errors.notes_too_long'));
            }

            $this->assertCurrentSnapshot($locked);
            $locked->update([
                'status' => VatReturn::STATUS_REVIEWED,
                'reviewed_by' => $actor->id,
                'reviewed_at' => now(),
                'review_notes' => $notes !== '' ? $notes : null,
                'review_confirmation_version' => VatReturn::REVIEW_CONFIRMATION_VERSION,
            ]);

            return $locked->fresh(['createdBy', 'reviewedBy']);
        }, 3);
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if ($return->status !== VatReturn::STATUS_DRAFT) {
            throw new DomainException(__('finance.vat_returns.errors.draft_only_review'));
        }

        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'approve_finance')) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_review'));
        }
    }

    private function assertCurrentSnapshot(VatReturn $return): void
    {
        $current = $this->snapshotService->build(
            $return->business_id,
            $return->period_from->toDateString(),
            $return->period_to->toDateString(),
        );

        if (! hash_equals($return->snapshot_hash, $current['hash'])) {
            throw new DomainException(__('finance.vat_returns.errors.snapshot_changed_review'));
        }
    }
}
