<?php

namespace App\Http\Controllers\Finance;

use App\Actions\Finance\ExportUniversalCreditAssessmentPeriodReportAction;
use App\Http\Controllers\Controller;
use App\Models\UniversalCreditAssessmentPeriod;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\Response;

class UniversalCreditAssessmentPeriodReportExportController extends Controller
{
    public function __invoke(
        UniversalCreditAssessmentPeriod $assessmentPeriod,
        string $format,
        ExportUniversalCreditAssessmentPeriodReportAction $export,
    ): Response {
        abort_unless(in_array($format, ['csv', 'xlsx', 'pdf'], true), 404);
        Gate::authorize('export', $assessmentPeriod);

        return $export->execute($assessmentPeriod, auth()->user(), $format);
    }
}
