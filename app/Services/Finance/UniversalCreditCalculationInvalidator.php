<?php

namespace App\Services\Finance;

use App\Models\UniversalCreditAssessmentPeriod;
use Illuminate\Support\Facades\DB;

class UniversalCreditCalculationInvalidator
{
    public function invalidate(UniversalCreditAssessmentPeriod $period): void
    {
        DB::table('universal_credit_assessment_periods')
            ->where('id', $period->id)
            ->where('business_id', $period->business_id)
            ->where('status', UniversalCreditAssessmentPeriod::STATUS_DRAFT)
            ->update([
                'income_total_pence' => 0,
                'expense_total_pence' => 0,
                'net_total_pence' => 0,
                'source_snapshot_hash' => null,
                'updated_at' => now(),
            ]);
    }
}
