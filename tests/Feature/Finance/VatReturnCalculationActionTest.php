<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\VatReturnCalculationAction;
use App\Models\AccountingProfile;
use App\Models\Business;
use App\Services\Finance\VatDetailService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class VatReturnCalculationActionTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'VAT Return Calculation Test',
            'slug' => 'vat-return-calculation-test',
            'is_active' => true,
        ]);
        $this->profile();
    }

    public function test_it_calculates_all_nine_boxes_and_hmrc_precision_from_tax_code_mappings(): void
    {
        $detail = $this->detail([
            $this->row('SALE-1', 'S20', [
                'output_vat' => [1], 'input_vat' => [4], 'net_sales' => [6], 'net_purchases' => [7],
            ], netSales: 100.99, outputVat: 20.00),
            $this->row('ACQ-1', 'NI-EU-ACQ', [
                'output_vat' => [2], 'input_vat' => [4], 'net_purchases' => [7, 9],
            ], netPurchases: 200.75, outputVat: 40.00, inputVat: 40.00),
            $this->row('SUPPLY-1', 'NI-EU-SUPPLY', [
                'net_sales' => [6, 8],
            ], netSales: 50.60),
            $this->row('PURCHASE-1', 'S20', [
                'output_vat' => [1], 'input_vat' => [4], 'net_sales' => [6], 'net_purchases' => [7],
            ], netPurchases: 50.49, inputVat: 10.00),
        ], outputVat: 60.00, inputVat: 50.00);

        $result = $this->calculate($detail);

        $this->assertSame(20.0, $result['boxes'][1]->submission_value);
        $this->assertSame(40.0, $result['boxes'][2]->submission_value);
        $this->assertSame(60.0, $result['boxes'][3]->submission_value);
        $this->assertSame(50.0, $result['boxes'][4]->submission_value);
        $this->assertSame(10.0, $result['boxes'][5]->submission_value);
        $this->assertSame(151, $result['boxes'][6]->submission_value);
        $this->assertSame(251, $result['boxes'][7]->submission_value);
        $this->assertSame(50, $result['boxes'][8]->submission_value);
        $this->assertSame(200, $result['boxes'][9]->submission_value);
        $this->assertSame(151.59, $result['boxes'][6]->raw_gbp);
        $this->assertSame(251.24, $result['boxes'][7]->raw_gbp);
        $this->assertSame(60.0, $result['hmrc_values']->totalVatDue);
        $this->assertSame(10.0, $result['hmrc_values']->netVatDue);
        $this->assertSame(200, $result['hmrc_values']->totalAcquisitionsExVAT);
        $this->assertSame('payable', $result['status']->net_direction);
        $this->assertSame(10.0, $result['status']->signed_net_vat_gbp);
        $this->assertTrue($result['status']->is_ready_for_review);
        $this->assertFalse($result['status']->is_submission);
        $this->assertTrue($result['checks']->box_8_included_in_box_6);
        $this->assertTrue($result['checks']->box_9_included_in_box_7);
        $this->assertCount(0, $result['issues']);
    }

    public function test_box_five_is_absolute_while_the_repayment_direction_preserves_the_signed_position(): void
    {
        $detail = $this->detail([
            $this->row('CREDIT-1', 'S20', [
                'output_vat' => [1], 'net_sales' => [6],
            ], netSales: -50.00, outputVat: -10.00),
            $this->row('PURCHASE-1', 'S20', [
                'input_vat' => [4], 'net_purchases' => [7],
            ], netPurchases: 100.00, inputVat: 30.00),
        ], outputVat: -10.00, inputVat: 30.00);

        $result = $this->calculate($detail);

        $this->assertSame(-10.0, $result['boxes'][1]->submission_value);
        $this->assertSame(-10.0, $result['boxes'][3]->submission_value);
        $this->assertSame(30.0, $result['boxes'][4]->submission_value);
        $this->assertSame(40.0, $result['boxes'][5]->submission_value);
        $this->assertSame('repayable', $result['status']->net_direction);
        $this->assertSame(-40.0, $result['status']->signed_net_vat_gbp);
        $this->assertTrue($result['status']->is_ready_for_review);
    }

    public function test_invalid_missing_and_unpaired_mappings_block_review_without_hiding_values(): void
    {
        $detail = $this->detail([
            $this->row('BAD-1', 'CUSTOM', [
                'output_vat' => [3], 'net_sales' => [8],
            ], netSales: 100.00, outputVat: 20.00),
            $this->row('UNKNOWN-1', 'UNKNOWN', [], inputVat: 5.00, taxCodeType: 'unknown'),
        ], outputVat: 20.00, inputVat: 5.00, adjustments: 1);

        $result = $this->calculate($detail);
        $codes = $result['issues']->pluck('code');

        $this->assertFalse($result['status']->is_ready_for_review);
        $this->assertContains('invalid_mapping_box', $codes);
        $this->assertContains('unmapped_amount', $codes);
        $this->assertContains('box_8_requires_box_6', $codes);
        $this->assertContains('output_vat_not_fully_mapped', $codes);
        $this->assertContains('input_vat_not_fully_mapped', $codes);
        $this->assertContains('ledger_adjustments_present', $result['warnings']->pluck('code'));
        $this->assertSame(100, $result['boxes'][8]->submission_value);
        $this->assertSame(0.0, $result['boxes'][1]->submission_value);
        $this->assertFalse($result['checks']->box_8_included_in_box_6);
    }

    public function test_profile_and_vat_effective_period_rules_block_unsupported_calculation(): void
    {
        $this->profile([
            'vat_scheme' => 'cash',
            'vat_effective_from' => '2026-02-01',
            'vat_effective_to' => '2026-02-28',
        ]);

        $result = $this->calculate($this->detail([], dateFrom: '2026-01-01', dateTo: '2026-03-31'));
        $codes = $result['issues']->pluck('code');

        $this->assertFalse($result['status']->is_ready_for_review);
        $this->assertContains('vat_scheme_not_supported', $codes);
        $this->assertContains('period_starts_before_vat_registration', $codes);
        $this->assertContains('period_ends_after_vat_registration', $codes);
        $this->assertSame('nil', $result['status']->net_direction);
        $this->assertSame(0.0, $result['boxes'][5]->submission_value);
    }

    public function test_outside_scope_base_is_explicitly_excluded_with_a_warning(): void
    {
        $detail = $this->detail([
            $this->row('OUTSIDE-1', 'OUTSIDE', [], netSales: 125.75, taxCodeType: 'outside_scope'),
        ]);

        $result = $this->calculate($detail);

        $this->assertTrue($result['status']->is_ready_for_review);
        $this->assertCount(0, $result['issues']);
        $this->assertContains('outside_scope_excluded', $result['warnings']->pluck('code'));
        $this->assertSame(0, $result['boxes'][6]->submission_value);
    }

    public function test_accounting_profile_lookup_is_isolated_by_business(): void
    {
        AccountingProfile::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->delete();
        $otherBusiness = Business::create([
            'name' => 'Other VAT Tenant',
            'slug' => 'other-vat-tenant',
            'is_active' => true,
        ]);
        AccountingProfile::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'legal_name' => 'Other VAT Tenant',
            'functional_currency' => 'GBP',
            'accounting_basis' => 'accrual',
            'vat_registered' => true,
            'vat_number' => 'GB987654321',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2020-01-01',
        ]);

        $result = $this->calculate($this->detail([]));

        $this->assertFalse($result['status']->is_ready_for_review);
        $this->assertContains('accounting_profile_missing', $result['issues']->pluck('code'));
    }

    /** @param array<string, mixed> $overrides */
    private function profile(array $overrides = []): AccountingProfile
    {
        return AccountingProfile::withoutGlobalScopes()->updateOrCreate(
            ['business_id' => $this->business->id],
            [
                'legal_name' => 'Website Expert NI',
                'functional_currency' => 'GBP',
                'accounting_basis' => 'accrual',
                'vat_registered' => true,
                'vat_number' => 'GB123456789',
                'vat_scheme' => 'standard',
                'vat_effective_from' => '2020-01-01',
                'vat_effective_to' => null,
                ...$overrides,
            ],
        );
    }

    /** @param array<string, mixed> $detail */
    private function calculate(array $detail): array
    {
        $service = Mockery::mock(VatDetailService::class);
        $service->shouldReceive('report')
            ->once()
            ->with($this->business->id, $detail['date_from'], $detail['date_to'])
            ->andReturn($detail);

        return (new VatReturnCalculationAction($service))->execute(
            $this->business->id,
            $detail['date_from'],
            $detail['date_to'],
        );
    }

    /**
     * @param  list<object>  $rows
     * @return array<string, mixed>
     */
    private function detail(
        array $rows,
        float $outputVat = 0,
        float $inputVat = 0,
        int $adjustments = 0,
        string $dateFrom = '2026-01-01',
        string $dateTo = '2026-03-31',
    ): array {
        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'rows' => collect($rows),
            'tax_codes' => collect($rows)
                ->unique('tax_code')
                ->map(fn (object $row): object => (object) [
                    'code' => $row->tax_code,
                    'is_active' => true,
                ])
                ->values(),
            'totals' => (object) [
                'output_vat_gbp' => $outputVat,
                'input_vat_gbp' => $inputVat,
                'is_reconciled' => true,
                'adjustment_count' => $adjustments,
            ],
        ];
    }

    /** @param array<string, mixed> $mapping */
    private function row(
        string $reference,
        string $taxCode,
        array $mapping,
        float $netSales = 0,
        float $netPurchases = 0,
        float $outputVat = 0,
        float $inputVat = 0,
        string $taxCodeType = 'standard',
    ): object {
        static $journalId = 0;

        return (object) [
            'journal_entry_id' => ++$journalId,
            'reference' => $reference,
            'tax_code_id' => $taxCode === 'UNKNOWN' ? null : $journalId,
            'tax_code' => $taxCode,
            'tax_code_type' => $taxCodeType,
            'vat_return_mapping' => $mapping,
            'net_sales_gbp' => $netSales,
            'net_purchases_gbp' => $netPurchases,
            'output_vat_gbp' => $outputVat,
            'input_vat_gbp' => $inputVat,
        ];
    }
}
