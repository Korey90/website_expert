<?php

namespace App\Actions\Finance;

use App\Data\Finance\UniversalCreditSourceCandidate;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditReportItem;
use App\Models\User;
use App\Services\Finance\FinancialAttachmentService;
use App\Services\Finance\UniversalCreditCategoryService;
use App\Support\PermissionHelper;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class CreateUniversalCreditManualEntryAction
{
    public function __construct(
        private readonly UniversalCreditCategoryService $categories,
        private readonly FinancialAttachmentService $attachments,
    ) {}

    /** @param array<string, mixed> $data */
    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        array $data,
        ?UploadedFile $evidence = null,
    ): UniversalCreditManualEntry {
        $this->authorize($period, $actor);
        $direction = trim((string) ($data['direction'] ?? ''));
        $eventType = trim((string) ($data['event_type'] ?? ''));
        $category = trim((string) ($data['category'] ?? ''));
        $cashDate = $this->date((string) ($data['cash_date'] ?? ''));
        $originalAmount = $this->positiveMoney($data['original_amount'] ?? null);
        $currency = strtoupper(trim((string) ($data['original_currency'] ?? 'GBP')));
        $description = trim((string) ($data['description'] ?? ''));
        $missingReason = trim((string) ($data['evidence_missing_reason'] ?? ''));

        if (! in_array($direction, UniversalCreditReportItem::directions(), true)
            || ! in_array($eventType, UniversalCreditManualEntry::eventTypes(), true)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_manual_entry'));
        }

        $this->assertEventDirection($eventType, $direction);

        if (! preg_match('/^[A-Z]{3}$/', $currency)) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_currency'));
        }

        if (mb_strlen($description) < 3 || mb_strlen($description) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_manual_description'));
        }

        if (mb_strlen($missingReason) > 2000) {
            throw new DomainException(__('finance.universal_credit.errors.text_too_long'));
        }

        if ($evidence === null && $missingReason === '') {
            throw new DomainException(__('finance.universal_credit.errors.manual_evidence_required'));
        }

        $amountGbpPence = $currency === 'GBP'
            ? $this->pence($originalAmount)
            : $this->pence($this->positiveMoney($data['amount_gbp'] ?? null));

        return DB::transaction(function () use (
            $period,
            $actor,
            $direction,
            $eventType,
            $category,
            $cashDate,
            $originalAmount,
            $currency,
            $amountGbpPence,
            $description,
            $missingReason,
            $evidence,
        ): UniversalCreditManualEntry {
            $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                ->where('business_id', $period->business_id)
                ->whereKey($period->id)
                ->lockForUpdate()
                ->firstOrFail();
            $this->authorize($locked, $actor);

            if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_DRAFT) {
                throw new DomainException(__('finance.universal_credit.errors.period_not_draft'));
            }

            if ($cashDate->lt($locked->date_from) || $cashDate->gt($locked->date_to)) {
                throw new DomainException(__('finance.universal_credit.errors.manual_date_outside_period'));
            }

            $this->categories->assertAllowed($locked, $direction, $category);

            $entry = UniversalCreditManualEntry::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'assessment_period_id' => $locked->id,
                'direction' => $direction,
                'event_type' => $eventType,
                'category' => $category,
                'cash_date' => $cashDate->toDateString(),
                'original_amount' => $originalAmount,
                'original_currency' => $currency,
                'amount_gbp_pence' => $amountGbpPence,
                'description' => $description,
                'evidence_missing_reason' => $evidence === null ? $missingReason : null,
                'status' => UniversalCreditManualEntry::STATUS_ACTIVE,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);
            $candidate = new UniversalCreditSourceCandidate(
                source: $entry,
                eventKey: 'manual',
                direction: $direction,
                category: $category,
                cashDate: $cashDate,
                documentDate: null,
                originalAmount: $originalAmount,
                originalCurrency: $currency,
                grossAmountPence: $amountGbpPence,
                context: [
                    'event_type' => $eventType,
                    'manual_entry' => true,
                ],
            );

            UniversalCreditReportItem::withoutGlobalScopes()->create([
                'business_id' => $locked->business_id,
                'assessment_period_id' => $locked->id,
                'source_type' => $entry->getMorphClass(),
                'source_id' => $entry->id,
                'source_event_key' => 'manual',
                'source_identity_hash' => $candidate->identityHash($locked),
                'source_fingerprint' => $candidate->fingerprint(),
                'direction' => $direction,
                'category' => $category,
                'classification_status' => UniversalCreditReportItem::STATUS_REVIEW_REQUIRED,
                'cash_date' => $cashDate->toDateString(),
                'original_amount' => $originalAmount,
                'original_currency' => $currency,
                'gross_amount_pence' => $amountGbpPence,
                'business_use_basis_points' => 10000,
                'calculation_method' => UniversalCreditReportItem::METHOD_MANUAL,
                'source_context' => $candidate->context,
                'source_is_current' => true,
            ]);

            if ($evidence !== null) {
                $this->attachments->store($entry, $evidence, $actor, 'other');
            }

            return $entry->fresh(['attachments', 'reportItems']);
        }, 3);
    }

    private function assertEventDirection(string $eventType, string $direction): void
    {
        $expected = match ($eventType) {
            UniversalCreditManualEntry::EVENT_CASH_RECEIPT,
            UniversalCreditManualEntry::EVENT_TIPS,
            UniversalCreditManualEntry::EVENT_GRANT,
            UniversalCreditManualEntry::EVENT_TAX_NI_REFUND,
            UniversalCreditManualEntry::EVENT_ASSET_TRANSFER => UniversalCreditReportItem::DIRECTION_INCOME,
            UniversalCreditManualEntry::EVENT_CASH_PAYMENT,
            UniversalCreditManualEntry::EVENT_TAX_NI_PENSION_VAT_PAYMENT => UniversalCreditReportItem::DIRECTION_EXPENSE,
            default => null,
        };

        if ($expected !== null && $direction !== $expected) {
            throw new DomainException(__('finance.universal_credit.errors.manual_event_direction'));
        }
    }

    private function date(string $value): CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat('!Y-m-d', $value);

            if ($date->format('Y-m-d') !== $value) {
                throw new \InvalidArgumentException('Non-canonical date.');
            }

            return $date;
        } catch (\Throwable $exception) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_manual_date'), previous: $exception);
        }
    }

    private function positiveMoney(mixed $value): string
    {
        $value = trim((string) $value);

        if (! preg_match('/^\d+(?:\.\d{1,2})?$/', $value) || (float) $value <= 0) {
            throw new DomainException(__('finance.universal_credit.errors.invalid_manual_amount'));
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function pence(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function authorize(UniversalCreditAssessmentPeriod $period, User $actor): void
    {
        $isMember = $actor->businessMemberships()
            ->where('business_id', $period->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $isMember || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
