<?php

namespace App\Actions\Finance;

use App\Models\FinancialAttachment;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Services\Finance\FinancialAttachmentService;
use App\Support\PermissionHelper;
use DomainException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class MarkUniversalCreditAssessmentPeriodReportedAction
{
    public function __construct(private readonly FinancialAttachmentService $attachments) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        ?string $confirmationReference = null,
        ?UploadedFile $confirmation = null,
    ): UniversalCreditAssessmentPeriod {
        $this->authorize($period, $actor);
        $confirmationReference = trim((string) $confirmationReference);

        if (mb_strlen($confirmationReference) > 150) {
            throw new DomainException(__('finance.universal_credit.errors.report_reference_too_long'));
        }

        $attachment = null;

        try {
            return DB::transaction(function () use (
                $period,
                $actor,
                $confirmationReference,
                $confirmation,
                &$attachment,
            ): UniversalCreditAssessmentPeriod {
                $locked = UniversalCreditAssessmentPeriod::withoutGlobalScopes()
                    ->where('business_id', $period->business_id)
                    ->whereKey($period->id)
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->authorize($locked, $actor);

                if ($locked->status !== UniversalCreditAssessmentPeriod::STATUS_LOCKED) {
                    throw new DomainException(__('finance.universal_credit.errors.period_not_locked'));
                }

                if ($confirmation !== null) {
                    $attachment = $this->attachments->store($locked, $confirmation, $actor, 'other');
                }

                $locked->update([
                    'status' => UniversalCreditAssessmentPeriod::STATUS_REPORTED,
                    'reported_by' => $actor->id,
                    'reported_at' => now(),
                    'report_confirmation_reference' => $confirmationReference !== '' ? $confirmationReference : null,
                ]);

                return $locked->fresh(['attachments']);
            }, 3);
        } catch (Throwable $exception) {
            if ($attachment instanceof FinancialAttachment
                && ! FinancialAttachment::withoutGlobalScopes()->whereKey($attachment->id)->exists()) {
                Storage::disk($attachment->disk)->delete($attachment->path);
            }

            throw $exception;
        }
    }

    private function authorize(UniversalCreditAssessmentPeriod $period, User $actor): void
    {
        $member = $actor->businessMemberships()
            ->where('business_id', $period->business_id)
            ->where('is_active', true)
            ->exists();

        if (! $member || ! PermissionHelper::allows($actor, 'manage_universal_credit_reporting')) {
            throw new DomainException(__('finance.universal_credit.errors.period_manage_denied'));
        }
    }
}
