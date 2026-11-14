<?php

namespace Tests\Feature\Filament;

use App\Filament\Pages\AgedReceivablesPayablesPage;
use App\Filament\Pages\BalanceSheetPage;
use App\Filament\Pages\BankReconciliationReportPage;
use App\Filament\Pages\ExpenseReportPage;
use App\Filament\Pages\ManagementCashFlowPage;
use App\Filament\Pages\ProfitAndLossPage;
use App\Filament\Pages\TrialBalancePage;
use App\Filament\Pages\VatDetailPage;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinancialReportsUiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Filament::setCurrentPanel('admin');
        app()->setLocale('en');
    }

    public function test_financial_reports_render_their_operational_context(): void
    {
        $admin = User::query()->where('email', '20noname22x@gmail.com')->firstOrFail();
        $this->actingAs($admin);

        $pages = [
            TrialBalancePage::class => 'trial_balance',
            ExpenseReportPage::class => 'expenses',
            ProfitAndLossPage::class => 'profit_and_loss',
            BalanceSheetPage::class => 'balance_sheet',
            BankReconciliationReportPage::class => 'bank_reconciliation',
            ManagementCashFlowPage::class => 'cash_flow',
            AgedReceivablesPayablesPage::class => 'aged_balances',
            VatDetailPage::class => 'vat_detail',
        ];

        foreach ($pages as $page => $translationKey) {
            $this->get($page::getUrl())
                ->assertOk()
                ->assertSeeText(__("admin.migration.reports.{$translationKey}"));
        }
    }
}
