<?php

namespace App\Actions\Finance;

use App\Models\Business;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\User;
use App\Services\Finance\FinanceReportExportService;
use App\Services\Finance\UniversalCreditReportDocumentFactory;
use App\Services\Finance\UniversalCreditReportModelService;
use App\Support\PermissionHelper;
use DomainException;
use Symfony\Component\HttpFoundation\Response;

class ExportUniversalCreditAssessmentPeriodReportAction
{
    public function __construct(
        private readonly UniversalCreditReportModelService $reports,
        private readonly UniversalCreditReportDocumentFactory $documents,
        private readonly FinanceReportExportService $exporter,
    ) {}

    public function execute(
        UniversalCreditAssessmentPeriod $period,
        User $actor,
        string $format,
    ): Response {
        $member = $actor->businessMemberships()->where('business_id', $period->business_id)
            ->where('is_active', true)->exists();

        if (! $member || ! PermissionHelper::allows($actor, 'export_universal_credit_pack')) {
            throw new DomainException(__('finance.universal_credit.errors.period_export_denied'));
        }

        $business = Business::withoutGlobalScopes()->findOrFail($period->business_id);
        $report = $this->reports->build($period);

        return $this->exporter->download($this->documents->make($business, $report), $format);
    }
}
