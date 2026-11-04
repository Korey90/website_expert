<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreatePurchaseCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssuePurchaseCreditNoteAction;
use App\Filament\Pages\ExpenseReportPage;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\Project;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Finance\ExpenseReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ExpenseReportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    private Project $project;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Reports Ltd', 'slug' => 'expense-reports', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->supplier = Supplier::factory()->for($this->business)->create(['name' => 'Report Supplier']);
        $client = Client::create(['business_id' => $this->business->id, 'company_name' => 'Report Client']);
        $this->project = Project::create([
            'business_id' => $this->business->id,
            'client_id' => $client->id,
            'title' => 'Report Project',
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
    }

    public function test_report_groups_gbp_totals_by_category_and_subtracts_issued_purchase_credits(): void
    {
        $software = $this->approvedExpense('2026-10-10', 'software', 'S20', 100, $this->project->id);
        $this->approvedExpense('2026-10-11', 'travel', 'Z0', 50);
        $credit = app(CreatePurchaseCreditNoteAction::class)->execute($software, $this->user, [
            'reason' => 'Partial supplier credit',
            'issue_date' => '2026-10-15',
            'items' => [['expense_item_id' => $software->items->first()->id, 'quantity' => 0.5]],
        ]);
        app(IssuePurchaseCreditNoteAction::class)->execute($credit, $this->user);

        $report = app(ExpenseReportService::class)->report($this->business->id, '2026-10-01', '2026-10-31', 'category');
        $softwareRow = $report['rows']->firstWhere('key', 'category:software');
        $travelRow = $report['rows']->firstWhere('key', 'category:travel');

        $this->assertSame(50.0, $softwareRow->net_gbp);
        $this->assertSame(10.0, $softwareRow->vat_gbp);
        $this->assertSame(60.0, $softwareRow->gross_gbp);
        $this->assertSame(2, $softwareRow->document_count);
        $this->assertSame(50.0, $travelRow->gross_gbp);
        $this->assertSame(100.0, $report['totals']->net_gbp);
        $this->assertSame(10.0, $report['totals']->vat_gbp);
        $this->assertSame(110.0, $report['totals']->gross_gbp);
        $this->assertSame(3, $report['totals']->document_count);
    }

    public function test_report_can_group_by_supplier_project_and_vat_code(): void
    {
        $this->approvedExpense('2026-09-01', 'professional_fees', 'S20', 80, $this->project->id);

        $supplier = app(ExpenseReportService::class)->report($this->business->id, '2026-09-01', '2026-09-30', 'supplier');
        $project = app(ExpenseReportService::class)->report($this->business->id, '2026-09-01', '2026-09-30', 'project');
        $tax = app(ExpenseReportService::class)->report($this->business->id, '2026-09-01', '2026-09-30', 'tax_code');

        $this->assertSame('Report Supplier', $supplier['rows']->sole()->label);
        $this->assertSame('Report Project', $project['rows']->sole()->label);
        $this->assertSame('S20', $tax['rows']->sole()->label);
        $this->assertSame(96.0, $tax['rows']->sole()->gross_gbp);
    }

    public function test_drafts_void_records_outside_period_and_other_tenants_are_excluded(): void
    {
        $this->draftExpense('2026-10-01', 'office', 'S20', 100);
        $this->approvedExpense('2025-10-01', 'office', 'S20', 100);
        $otherBusiness = Business::create(['name' => 'Other Reports Ltd', 'slug' => 'other-expense-reports', 'is_active' => true]);
        $otherUser = User::factory()->create();
        BusinessUser::create(['business_id' => $otherBusiness->id, 'user_id' => $otherUser->id, 'role' => 'owner', 'is_active' => true]);
        $otherSupplier = Supplier::factory()->for($otherBusiness)->create();
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $expense = app(CreateExpenseAction::class)->execute($otherBusiness, $otherUser, [
            'supplier_id' => $otherSupplier->id,
            'issue_date' => '2026-10-01',
            'business_purpose' => 'Other tenant',
            'items' => [['description' => 'Private', 'quantity' => 1, 'unit_price' => 100, 'category' => 'office', 'tax_code_id' => TaxCode::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', 'S20')->value('id')]],
        ]);
        app(ApproveExpenseAction::class)->execute($expense, $otherUser);

        $report = app(ExpenseReportService::class)->report($this->business->id, '2026-10-01', '2026-10-31', 'category');

        $this->assertTrue($report['rows']->isEmpty());
        $this->assertSame(0.0, $report['totals']->gross_gbp);
    }

    public function test_authorized_user_can_open_report_and_export_csv(): void
    {
        foreach (['access_admin_panel', 'view_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance']);
        $this->approvedExpense(today()->toDateString(), 'software', 'S20', 25);

        $directReport = app(ExpenseReportService::class)->report(
            $this->business->id,
            today()->startOfYear()->toDateString(),
            today()->toDateString(),
            'category',
        );
        $this->assertCount(1, $directReport['rows']);

        $this->actingAs($this->user)
            ->get(ExpenseReportPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.expense_report.title'));
        $response = $this->get(route('admin.finance.expense-report.csv', [
            'date_from' => today()->startOfYear()->toDateString(),
            'date_to' => today()->toDateString(),
            'group_by' => 'category',
        ]));
        $response->assertOk()->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Software', $response->streamedContent());
    }

    public function test_user_without_finance_permission_cannot_export_the_report(): void
    {
        $this->actingAs($this->user)
            ->get(route('admin.finance.expense-report.csv', [
                'date_from' => '2026-01-01',
                'date_to' => '2026-12-31',
                'group_by' => 'category',
            ]))
            ->assertForbidden();
    }

    private function approvedExpense(string $date, string $category, string $taxCode, float $price, ?int $projectId = null): Expense
    {
        return app(ApproveExpenseAction::class)->execute(
            $this->draftExpense($date, $category, $taxCode, $price, $projectId),
            $this->user,
        );
    }

    private function draftExpense(string $date, string $category, string $taxCode, float $price, ?int $projectId = null): Expense
    {
        return app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'project_id' => $projectId,
            'issue_date' => $date,
            'business_purpose' => 'Expense report test',
            'items' => [[
                'description' => ucfirst($category),
                'quantity' => 1,
                'unit_price' => $price,
                'category' => $category,
                'project_id' => $projectId,
                'tax_code_id' => TaxCode::withoutGlobalScopes()
                    ->where('business_id', $this->business->id)
                    ->where('code', $taxCode)
                    ->value('id'),
            ]],
        ]);
    }
}
