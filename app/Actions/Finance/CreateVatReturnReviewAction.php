<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreateVatReturnReviewAction
{
    public function __construct(
        private readonly GenerateDocumentNumberAction $generateDocumentNumber,
        private readonly VatReturnSnapshotService $snapshotService,
    ) {}

    public function execute(Business $business, User $actor, string $dateFrom, string $dateTo): VatReturn
    {
        $from = $this->date($dateFrom, __('finance.vat_returns.fields.period_from'));
        $to = $this->date($dateTo, __('finance.vat_returns.fields.period_to'));

        if ($from->gt($to)) {
            throw new DomainException(__('finance.vat_returns.errors.start_after_end'));
        }

        return DB::transaction(function () use ($business, $actor, $from, $to): VatReturn {
            Business::query()->lockForUpdate()->findOrFail($business->id);
            $this->assertAuthorized($business->id, $actor);

            $overlap = VatReturn::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->whereIn('status', VatReturn::activeStatuses())
                ->whereDate('period_from', '<=', $to->toDateString())
                ->whereDate('period_to', '>=', $from->toDateString())
                ->first();

            if ($overlap) {
                throw new DomainException(__('finance.vat_returns.errors.overlap', ['number' => $overlap->number]));
            }

            $built = $this->snapshotService->build($business->id, $from->toDateString(), $to->toDateString());

            return VatReturn::withoutGlobalScopes()->create([
                'business_id' => $business->id,
                'number' => $this->generateDocumentNumber->execute($business->id, 'vat_return', $to),
                'status' => VatReturn::STATUS_DRAFT,
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'snapshot_version' => VatReturnSnapshotService::SCHEMA_VERSION,
                'snapshot' => $built['snapshot'],
                'snapshot_hash' => $built['hash'],
                'source_journal_count' => $built['source_journal_count'],
                'source_line_count' => $built['source_line_count'],
                'warning_count' => $built['warning_count'],
                'created_by' => $actor->id,
            ]);
        }, 3);
    }

    private function assertAuthorized(string $businessId, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $businessId)->where('is_active', true)->exists()) {
            throw new DomainException(__('finance.vat_returns.errors.not_member'));
        }

        if (! PermissionHelper::allows($actor, 'manage_finance')) {
            throw new DomainException(__('finance.vat_returns.errors.cannot_create'));
        }
    }

    private function date(string $value, string $label): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);
        } catch (Throwable) {
            $date = null;
        }

        if (! $date || $date->toDateString() !== $value) {
            throw new DomainException(__('finance.vat_returns.errors.invalid_date', ['label' => $label]));
        }

        return $date;
    }
}
