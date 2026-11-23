<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CalculateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\ClassifyUniversalCreditReportItemAction;
use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\CreateUniversalCreditManualEntryAction;
use App\Actions\Finance\CreateUniversalCreditSimplifiedExpenseAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\SetUniversalCreditGbpSnapshotAction;
use App\Actions\Finance\SetUniversalCreditVatSnapshotAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Actions\Finance\VoidUniversalCreditSimplifiedExpenseAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditManualEntry;
use App\Models\UniversalCreditProfile;
use App\Models\UniversalCreditReportItem;
use App\Models\UniversalCreditSimplifiedExpense;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UniversalCreditCalculatorsTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private UniversalCreditAssessmentPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('financial');
        Queue::fake();
        Carbon::setTestNow('2026-12-01 10:00:00');
        CarbonImmutable::setTestNow('2026-12-01 10:00:00');
        $this->business = Business::create([
            'name' => 'UC Calculator Sole Trader',
            'slug' => 'uc-calculator-sole-trader',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->owner->givePermissionTo([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
        ]);
        $this->actingAs($this->owner);
        app(InitializeAccountingSetupAction::class)
            ->execute($this->business, $this->owner)['profile']
            ->update([
                'entity_type' => 'sole_trader',
                'vat_registered' => false,
            ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        $this->period = $this->createPeriod();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_calculates_cash_values_business_use_and_invalidates_stale_totals(): void
    {
        $income = $this->manualItem('income', '100.00', 'goods_and_services', UniversalCreditManualEntry::EVENT_OTHER);
        $expense = $this->manualItem('expense', '50.00', 'other_business_expense', UniversalCreditManualEntry::EVENT_CASH_PAYMENT);
        $this->classify($income, 'goods_and_services');
        $this->classify($expense, 'other_business_expense', 50, 'Half of the cost is private use.');

        $calculated = app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($this->period, $this->owner);

        $this->assertSame(10000, $calculated->income_total_pence);
        $this->assertSame(2500, $calculated->expense_total_pence);
        $this->assertSame(7500, $calculated->net_total_pence);
        $this->assertSame(64, strlen((string) $calculated->source_snapshot_hash));
        $this->assertSame(2500, $expense->fresh()->reportable_amount_pence);

        $this->classify($expense->fresh(), 'other_business_expense');
        $this->period->refresh();

        $this->assertSame(0, $this->period->income_total_pence);
        $this->assertSame(0, $this->period->expense_total_pence);
        $this->assertNull($this->period->source_snapshot_hash);
    }

    public function test_vat_exclusive_period_requires_and_subtracts_an_explicit_vat_snapshot(): void
    {
        $this->period->update(['status' => UniversalCreditAssessmentPeriod::STATUS_SUPERSEDED]);
        $this->business->accountingProfile()->withoutGlobalScopes()->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2026-12-23',
        ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
            'vat_reporting_method' => UniversalCreditProfile::VAT_EXCLUSIVE,
            'vat_method_effective_from' => '2026-12-23',
        ]);
        $this->period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-12-23',
            '2027-01-21',
        );
        $item = $this->manualItem('expense', '12.00', 'office_and_equipment', UniversalCreditManualEntry::EVENT_CASH_PAYMENT, '2026-12-24');
        $this->classify($item, 'office_and_equipment');

        try {
            app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($this->period, $this->owner);
            $this->fail('VAT-exclusive calculation must require a VAT snapshot.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.vat_snapshot_required'), $exception->getMessage());
        }

        app(SetUniversalCreditVatSnapshotAction::class)->execute(
            $item,
            $this->owner,
            '2.00',
            'VAT copied from the cash receipt.',
        );
        $calculated = app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($this->period, $this->owner);

        $this->assertSame(1000, $calculated->expense_total_pence);
        $this->assertSame(-1000, $calculated->net_total_pence);
        $this->assertDatabaseHas('universal_credit_report_items', [
            'id' => $item->id,
            'vat_amount_pence' => 200,
            'vat_snapshot_source' => 'manual',
        ]);
    }

    public function test_simplified_expenses_use_policy_rates_and_are_included_in_the_period_total(): void
    {
        $action = app(CreateUniversalCreditSimplifiedExpenseAction::class);
        $vehicle = $action->execute(
            $this->period,
            $this->owner,
            UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE,
            ['vehicle_type' => 'car_van_or_other_motor_vehicle', 'miles' => '1000.00'],
            evidenceMissingReason: 'Mileage log will be attached before review.',
        );
        $home = $action->execute(
            $this->period,
            $this->owner,
            UniversalCreditSimplifiedExpense::TYPE_HOME_WORKING,
            ['hours' => 75],
            evidenceMissingReason: 'Calendar evidence will be attached before review.',
        );
        $living = $action->execute(
            $this->period,
            $this->owner,
            UniversalCreditSimplifiedExpense::TYPE_BUSINESS_PREMISES_LIVING,
            ['actual_cost_gbp' => '800.00', 'resident_band' => 'one_person'],
            evidenceMissingReason: 'Premises bill will be attached before review.',
        );

        $this->assertSame(41660, $vehicle->calculated_amount_pence);
        $this->assertSame(1800, $home->calculated_amount_pence);
        $this->assertSame(45000, $living->calculated_amount_pence);

        $calculated = app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($this->period, $this->owner);
        $this->assertSame(88460, $calculated->expense_total_pence);

        app(VoidUniversalCreditSimplifiedExpenseAction::class)->execute(
            $home,
            $this->owner,
            'The working-hours record was entered for the wrong month.',
        );
        $this->period->refresh();
        $this->assertSame(0, $this->period->expense_total_pence);
        $this->assertNull($this->period->source_snapshot_hash);
    }

    public function test_simplified_and_actual_costs_cannot_be_claimed_twice(): void
    {
        $item = $this->manualItem('expense', '30.00', 'other_vehicle_and_business_travel', UniversalCreditManualEntry::EVENT_CASH_PAYMENT);
        $this->classify($item, 'other_vehicle_and_business_travel');
        $action = app(CreateUniversalCreditSimplifiedExpenseAction::class);

        try {
            $action->execute(
                $this->period,
                $this->owner,
                UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE,
                ['vehicle_type' => 'motorbike_or_scooter', 'miles' => 10],
                evidenceMissingReason: 'Mileage log pending.',
            );
            $this->fail('Actual vehicle costs and simplified mileage must conflict.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.simplified_double_claim'), $exception->getMessage());
        }

        app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item,
            $this->owner,
            UniversalCreditReportItem::STATUS_EXCLUDED,
            'other_vehicle_and_business_travel',
            classificationReason: 'Use simplified mileage instead.',
        );
        $action->execute(
            $this->period,
            $this->owner,
            UniversalCreditSimplifiedExpense::TYPE_VEHICLE_MILEAGE,
            ['vehicle_type' => 'motorbike_or_scooter', 'miles' => 10],
            evidenceMissingReason: 'Mileage log pending.',
        );

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.universal_credit.errors.simplified_double_claim'));
        $this->classify($item->fresh(), 'other_vehicle_and_business_travel');
    }

    public function test_business_loan_interest_is_capped_at_the_policy_monthly_limit(): void
    {
        $first = $this->manualItem('expense', '30.00', 'legal_financial_and_insurance', UniversalCreditManualEntry::EVENT_CASH_PAYMENT);
        $second = $this->manualItem('expense', '30.00', 'legal_financial_and_insurance', UniversalCreditManualEntry::EVENT_CASH_PAYMENT);

        foreach ([$first, $second] as $item) {
            app(ClassifyUniversalCreditReportItemAction::class)->execute(
                item: $item,
                actor: $this->owner,
                classificationStatus: UniversalCreditReportItem::STATUS_INCLUDED,
                category: 'legal_financial_and_insurance',
                calculationRule: UniversalCreditReportItem::RULE_BUSINESS_LOAN_INTEREST,
            );
        }

        $calculated = app(CalculateUniversalCreditAssessmentPeriodAction::class)->execute($this->period, $this->owner);

        $this->assertSame(4100, $calculated->expense_total_pence);
        $this->assertSame(3000, $first->fresh()->reportable_amount_pence);
        $this->assertSame(1100, $second->fresh()->reportable_amount_pence);
        $this->artisan('finance:audit-tenants')->assertSuccessful();
    }

    public function test_foreign_currency_snapshot_uses_integer_rounding_and_can_be_replaced_with_a_reason(): void
    {
        $entry = app(CreateUniversalCreditManualEntryAction::class)->execute($this->period, $this->owner, [
            'direction' => UniversalCreditReportItem::DIRECTION_INCOME,
            'event_type' => UniversalCreditManualEntry::EVENT_OTHER,
            'category' => 'other_business_income',
            'cash_date' => '2026-12-05',
            'original_amount' => '10.00',
            'original_currency' => 'EUR',
            'gbp_exchange_rate' => '0.84555000',
            'exchange_rate_source' => 'Test daily rate',
            'exchange_rate_date' => '2026-12-05',
            'description' => 'Foreign-currency calculator test.',
            'evidence_missing_reason' => 'Test evidence is intentionally omitted.',
        ]);
        $item = $entry->reportItems->firstOrFail();

        $this->assertSame(846, $entry->amount_gbp_pence);
        $this->assertSame(846, $item->gross_amount_pence);
        $this->assertSame('0.84555000', $item->gbp_exchange_rate);

        $updated = app(SetUniversalCreditGbpSnapshotAction::class)->execute(
            $item,
            $this->owner,
            '0.80000000',
            'Corrected test daily rate',
            '2026-12-05',
            'The original rate source used the wrong publication date.',
        );
        $this->assertSame(800, $updated->gross_amount_pence);
        $this->assertSame('0.80000000', $updated->gbp_exchange_rate);
        $this->assertNotNull($updated->fx_snapshot_reason);
    }

    private function createPeriod(): UniversalCreditAssessmentPeriod
    {
        return app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );
    }

    private function manualItem(
        string $direction,
        string $amount,
        string $category,
        string $eventType,
        string $cashDate = '2026-12-05',
    ): UniversalCreditReportItem {
        $entry = app(CreateUniversalCreditManualEntryAction::class)->execute($this->period, $this->owner, [
            'direction' => $direction,
            'event_type' => $eventType,
            'category' => $category,
            'cash_date' => $cashDate,
            'original_amount' => $amount,
            'original_currency' => 'GBP',
            'description' => "Manual {$direction} for calculator test.",
            'evidence_missing_reason' => 'Test evidence is intentionally omitted.',
        ]);

        return $entry->reportItems->firstOrFail();
    }

    private function classify(
        UniversalCreditReportItem $item,
        string $category,
        int|float|string $businessUsePercentage = 100,
        ?string $businessUseReason = null,
    ): UniversalCreditReportItem {
        return app(ClassifyUniversalCreditReportItemAction::class)->execute(
            $item,
            $this->owner,
            UniversalCreditReportItem::STATUS_INCLUDED,
            $category,
            $businessUsePercentage,
            $businessUseReason,
        );
    }
}
