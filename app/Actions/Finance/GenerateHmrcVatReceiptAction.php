<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;
use App\Models\HmrcVatSubmissionEvent;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use App\Services\Hmrc\HmrcVatReturnPayload;
use App\Services\Hmrc\HmrcVatSubmissionAuditService;
use App\Support\PermissionHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use DomainException;

final class GenerateHmrcVatReceiptAction
{
    public function __construct(
        private readonly VatReturnSnapshotService $snapshotService,
        private readonly HmrcVatReturnPayload $payloadService,
        private readonly HmrcVatSubmissionAuditService $audit,
    ) {}

    /** @return array{content: string, filename: string, sha256: string} */
    public function execute(VatReturn $return, User $actor): array
    {
        $lockedReturn = VatReturn::withoutGlobalScopes()->findOrFail($return->id);
        $this->assertAuthorized($lockedReturn, $actor);
        $submission = HmrcVatSubmission::withoutGlobalScopes()
            ->with(['submittedBy', 'events.actor'])
            ->where('business_id', $lockedReturn->business_id)
            ->where('vat_return_id', $lockedReturn->id)
            ->where('environment', config('hmrc.environment', 'sandbox'))
            ->first();

        if (! $submission || $submission->status !== HmrcVatSubmission::STATUS_SUBMITTED
            || ! $submission->submitted_at || ! $submission->processing_date
            || ! is_string($submission->form_bundle_number)
            || preg_match('/^\d{12}$/', $submission->form_bundle_number) !== 1
            || ($submission->charge_reference !== null
                && (! is_string($submission->charge_reference)
                    || mb_strlen($submission->charge_reference) < 1
                    || mb_strlen($submission->charge_reference) > 16
                    || preg_match('/[\x00-\x1F\x7F]/', $submission->charge_reference) === 1))) {
            throw new DomainException(__('finance.vat_returns.errors.receipt_unavailable'));
        }

        $obligation = HmrcVatObligation::withoutGlobalScopes()
            ->whereKey($submission->hmrc_vat_obligation_id)
            ->where('business_id', $lockedReturn->business_id)
            ->where('environment', $submission->environment)
            ->first();

        if (! $obligation || ! hash_equals($submission->snapshot_hash, $lockedReturn->snapshot_hash)
            || ! hash_equals($lockedReturn->snapshot_hash, $this->snapshotService->hash($lockedReturn->snapshot))) {
            throw new DomainException(__('finance.vat_returns.errors.receipt_integrity'));
        }

        $payload = $this->payloadService->build($lockedReturn, (string) $obligation->period_key);

        if (! hash_equals($submission->payload_hash, $this->payloadService->hash($payload))) {
            throw new DomainException(__('finance.vat_returns.errors.receipt_integrity'));
        }

        $audit = $this->audit->verify($submission);
        $business = Business::query()->findOrFail($lockedReturn->business_id);
        $generatedAt = now()->utc()->startOfSecond();
        $events = HmrcVatSubmissionEvent::withoutGlobalScopes()
            ->with('actor')
            ->where('business_id', $lockedReturn->business_id)
            ->where('hmrc_vat_submission_id', $submission->id)
            ->orderBy('sequence')
            ->get();
        $content = Pdf::loadView('pdf.hmrc-vat-receipt', [
            'business' => $business,
            'return' => $lockedReturn,
            'submission' => $submission,
            'events' => $events,
            'audit' => $audit,
            'generatedAt' => $generatedAt,
            'generatedBy' => $actor,
        ])->setPaper('a4')->output();
        $hash = hash('sha256', $content);
        $this->audit->record(
            $submission,
            HmrcVatSubmissionAuditService::EVENT_RECEIPT_GENERATED,
            $submission->status,
            $actor,
            [
                'document_sha256' => $hash,
                'audit_head_hash' => $audit['head_hash'],
            ],
        );

        return [
            'content' => $content,
            'filename' => 'hmrc-vat-receipt-'.$this->safeName($lockedReturn->number).'-'.substr($submission->payload_hash, 0, 12).'.pdf',
            'sha256' => $hash,
        ];
    }

    private function assertAuthorized(VatReturn $return, User $actor): void
    {
        if (! $actor->businessMemberships()->where('business_id', $return->business_id)->where('is_active', true)->exists()
            || ! PermissionHelper::allows($actor, 'submit_tax_returns')) {
            throw new DomainException(__('finance.vat_returns.errors.receipt_forbidden'));
        }
    }

    private function safeName(string $value): string
    {
        $name = preg_replace('/[^A-Za-z0-9_-]+/', '-', $value) ?? 'vat-return';

        return trim($name, '-') ?: 'vat-return';
    }
}
