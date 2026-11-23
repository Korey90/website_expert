<?php

namespace App\Services\Finance;

use App\Data\Finance\UniversalCreditPreflightResult;
use App\Models\UniversalCreditAssessmentPeriod;

class UniversalCreditReviewSnapshotService
{
    public const VERSION = 1;

    public function __construct(
        private readonly UniversalCreditReportModelService $reportModel,
        private readonly UniversalCreditCalculationHashService $hasher,
    ) {}

    /** @return array<string, mixed> */
    public function build(UniversalCreditAssessmentPeriod $period, UniversalCreditPreflightResult $preflight): array
    {
        return [
            'schema_version' => self::VERSION,
            'report' => $this->reportModel->build($period),
            'preflight' => $preflight->toArray(),
        ];
    }

    /** @param array<string, mixed> $snapshot */
    public function hash(array $snapshot): string
    {
        return $this->hasher->hash($snapshot);
    }
}
