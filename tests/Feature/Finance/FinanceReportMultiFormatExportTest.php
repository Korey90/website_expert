<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FinanceReportMultiFormatExportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-report-exports',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        foreach (['access_admin_panel', 'view_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->period = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_all_finance_reports_export_valid_private_xlsx_and_pdf_files(): void
    {
        $this->actingAs($this->user);

        foreach ($this->reports() as $report => $parameters) {
            $xlsx = $this->get(route('admin.finance.'.$report.'.xlsx', $parameters));
            $xlsx->assertOk()
                ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
                ->assertHeader('x-content-type-options', 'nosniff');
            $this->assertPrivateNoStore($xlsx->headers->get('cache-control'));
            $xlsxContent = $xlsx->streamedContent();
            $this->assertStringStartsWith("PK\x03\x04", $xlsxContent);
            $this->assertValidSpreadsheet($xlsxContent);

            $pdf = $this->get(route('admin.finance.'.$report.'.pdf', $parameters));
            $pdf->assertOk()
                ->assertHeader('content-type', 'application/pdf')
                ->assertHeader('x-content-type-options', 'nosniff');
            $this->assertPrivateNoStore($pdf->headers->get('cache-control'));
            $this->assertStringStartsWith('%PDF-', (string) $pdf->getContent());
        }
    }

    public function test_xlsx_and_pdf_exports_require_finance_permission(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $parameters = ['period_id' => $this->period->id];
        $this->actingAs($member)
            ->get(route('admin.finance.trial-balance.xlsx', $parameters))
            ->assertForbidden();
        $this->get(route('admin.finance.trial-balance.pdf', $parameters))
            ->assertForbidden();
    }

    /** @return array<string, array<string, int|string>> */
    private function reports(): array
    {
        return [
            'trial-balance' => ['period_id' => $this->period->id],
            'expense-report' => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31', 'group_by' => 'category'],
            'profit-and-loss' => ['period_id' => $this->period->id],
            'balance-sheet' => ['period_id' => $this->period->id],
            'bank-reconciliation-report' => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
            'management-cash-flow' => ['period_id' => $this->period->id],
            'aged-receivables-payables' => ['as_of' => '2026-12-31'],
            'vat-detail' => ['date_from' => '2026-01-01', 'date_to' => '2026-12-31'],
        ];
    }

    private function assertValidSpreadsheet(string $content): void
    {
        $path = tempnam(sys_get_temp_dir(), 'finance-report-');
        $this->assertNotFalse($path);

        try {
            file_put_contents($path, $content);
            $spreadsheet = IOFactory::load($path);
            $this->assertNotSame('', (string) $spreadsheet->getActiveSheet()->getCell('A1')->getValue());
            $spreadsheet->disconnectWorksheets();
        } finally {
            @unlink($path);
        }
    }

    private function assertPrivateNoStore(?string $header): void
    {
        $this->assertNotNull($header);
        $this->assertStringContainsString('private', $header);
        $this->assertStringContainsString('no-store', $header);
    }
}
