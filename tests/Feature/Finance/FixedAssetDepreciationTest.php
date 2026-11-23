<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\AcquireFixedAssetAction;
use App\Actions\Finance\ApproveExpenseAction;
use App\Actions\Finance\CreateExpenseAction;
use App\Actions\Finance\CreateFixedAssetAction;
use App\Actions\Finance\CreateFixedAssetCategoryAction;
use App\Actions\Finance\DisposeFixedAssetAction;
use App\Actions\Finance\GenerateFixedAssetDepreciationScheduleAction;
use App\Actions\Finance\ImproveFixedAssetAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\PostExpenseAction;
use App\Actions\Finance\PostFixedAssetDepreciationAction;
use App\Actions\Finance\ReverseFixedAssetDepreciationAction;
use App\Actions\Finance\ReverseFixedAssetTransactionAction;
use App\Actions\Finance\SetFixedAssetDepreciationRateAction;
use App\Filament\Resources\FixedAssetResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Expense;
use App\Models\FinancialAttachment;
use App\Models\FixedAsset;
use App\Models\FixedAssetCategory;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetTransaction;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use App\Models\TaxCode;
use App\Models\User;
use App\Services\Finance\BalanceSheetService;
use App\Services\Finance\FixedAssetCostBasisService;
use App\Services\Finance\ProfitAndLossService;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class FixedAssetDepreciationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Supplier $supplier;

    private Client $client;

    private FixedAssetCategory $category;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        CarbonImmutable::setTestNow('2026-12-31 12:00:00');
        $this->business = Business::create(['name' => 'Depreciation Sole Trader', 'slug' => 'depreciation-sole-trader', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance', 'post_finance']);
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
        $this->supplier = Supplier::factory()->for($this->business)->create();
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Asset Buyer',
            'status' => 'active',
        ]);
        $this->category = app(CreateFixedAssetCategoryAction::class)->execute($this->business, $this->user, [
            'code' => 'equipment',
            'name' => ['en' => 'Equipment', 'pl' => 'Wyposażenie'],
            'asset_account_id' => $this->account('1500')->id,
            'accumulated_depreciation_account_id' => $this->account('1600')->id,
            'depreciation_expense_account_id' => $this->account('7000')->id,
            'default_depreciation_method' => FixedAssetCategory::METHOD_STRAIGHT_LINE,
            'default_useful_life_months' => 12,
            'default_residual_value_percent' => 10,
        ]);
    }

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();

        parent::tearDown();
    }

    public function test_straight_line_schedule_rounds_to_residual_and_posts_and_reverses_exactly(): void
    {
        $asset = $this->activeAsset('Straight line computer', 1200, '2026-01-15');
        $entries = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user);

        $this->assertCount(13, $entries);
        $this->assertSame('2026-01-15', $entries->first()->period_start->toDateString());
        $this->assertSame('2027-01-14', $entries->last()->period_end->toDateString());
        $this->assertSame(108000, $entries->sum(fn (FixedAssetDepreciationEntry $entry): int => $this->minor($entry->amount_gbp)));
        $this->assertSame('120.00', $entries->last()->closing_net_book_value_gbp);

        $first = $entries->first();
        $posted = app(PostFixedAssetDepreciationAction::class)->execute($first, $this->user);
        $this->assertSame(FixedAssetDepreciationEntry::STATUS_POSTED, $posted->status);
        $this->assertSame($first->amount_gbp, $asset->fresh()->accumulated_depreciation_gbp);
        $this->assertJournalAmounts($posted->journalEntry, [
            '7000' => [$first->amount_gbp, '0.00'],
            '1600' => ['0.00', $first->amount_gbp],
        ]);
        $profitAndLossRows = app(ProfitAndLossService::class)
            ->report($this->business->id, $this->period->id)['sections']
            ->flatMap(fn (object $section) => $section->rows)
            ->keyBy('code');
        $balanceSheetRows = app(BalanceSheetService::class)
            ->report($this->business->id, $this->period->id)['sections']
            ->flatMap(fn (object $section) => $section->rows)
            ->reject(fn (object $row): bool => $row->is_synthetic)
            ->keyBy('code');
        $this->assertSame((float) $first->amount_gbp, $profitAndLossRows['7000']->amount_gbp);
        $this->assertSame(-(float) $first->amount_gbp, $balanceSheetRows['1600']->amount_gbp);

        $retried = app(PostFixedAssetDepreciationAction::class)->execute($posted, $this->user);
        $this->assertSame($posted->journal_entry_id, $retried->journal_entry_id);

        $reversed = app(ReverseFixedAssetDepreciationAction::class)->execute(
            $posted,
            $this->user,
            'Correct the in-service date',
            '2026-02-01',
        );
        $this->assertSame(FixedAssetDepreciationEntry::STATUS_REVERSED, $reversed->status);
        $this->assertSame('0.00', $asset->fresh()->accumulated_depreciation_gbp);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $posted->journalEntry->fresh()->status);
        $this->assertSame($posted->journal_entry_id, $reversed->reversalJournalEntry->reversal_of_id);
        $this->assertSame(0, FixedAssetDepreciationEntry::query()->where('fixed_asset_id', $asset->id)->where('status', 'planned')->count());

        app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset->fresh(), $this->user);
        $this->assertSame(13, FixedAssetDepreciationEntry::query()->where('fixed_asset_id', $asset->id)->where('status', 'planned')->count());
    }

    public function test_posting_requires_the_next_due_period_permission_and_an_open_accounting_period(): void
    {
        $asset = $this->activeAsset('Posting guards computer', 1200, '2026-01-15');
        $entries = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user, '2026-02-28');

        try {
            app(PostFixedAssetDepreciationAction::class)->execute($entries->last(), $this->user);
            $this->fail('A later depreciation period should not post first.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('earlier depreciation', $exception->getMessage());
        }

        $this->user->revokePermissionTo('post_finance');
        try {
            app(PostFixedAssetDepreciationAction::class)->execute($entries->first(), $this->user);
            $this->fail('Posting without post_finance should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot post fixed asset', $exception->getMessage());
        }
        $this->user->givePermissionTo('post_finance');

        DB::table('accounting_periods')->where('business_id', $this->business->id)->update(['status' => AccountingPeriod::STATUS_CLOSED]);
        try {
            app(PostFixedAssetDepreciationAction::class)->execute($entries->first(), $this->user);
            $this->fail('Posting into a closed period should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertSame(FixedAssetDepreciationEntry::STATUS_PLANNED, $entries->first()->fresh()->status);
        $this->assertSame('0.00', $asset->fresh()->accumulated_depreciation_gbp);
    }

    public function test_disposal_requires_and_uses_depreciation_through_the_exact_sale_date(): void
    {
        $asset = $this->activeAsset('Computer for sale', 1200, '2026-01-15');
        [$invoice, $invoiceItem] = $this->issuedInvoice(1000, '2026-02-10');

        try {
            app(DisposeFixedAssetAction::class)->execute($asset, $invoiceItem, $this->user, '2026-02-10');
            $this->fail('A disposal with missing depreciation should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('through the disposal date', $exception->getMessage());
        }

        $entries = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user, '2026-02-10');
        $this->assertCount(2, $entries);
        $this->assertSame('2026-02-10', $entries->last()->period_end->toDateString());
        foreach ($entries as $entry) {
            app(PostFixedAssetDepreciationAction::class)->execute($entry, $this->user);
        }

        $asset = app(DisposeFixedAssetAction::class)->execute($asset->fresh(), $invoiceItem, $this->user, '2026-02-10');
        $disposal = $asset->transactions->firstWhere('type', FixedAssetTransaction::TYPE_DISPOSAL);
        $this->assertSame(FixedAsset::STATUS_DISPOSED, $asset->status);
        $this->assertSame($asset->accumulated_depreciation_gbp, $disposal->accumulated_depreciation_gbp);
        $this->assertSame(
            $this->minor($invoiceItem->fresh()->quantity * $invoiceItem->fresh()->unit_price) - (120000 - $this->minor($asset->accumulated_depreciation_gbp)),
            $this->signedMinor($asset->disposal_gain_loss_gbp),
        );
        $this->assertSame(0, FixedAssetDepreciationEntry::query()->where('fixed_asset_id', $asset->id)->where('status', 'planned')->count());
        $this->assertNotNull($invoice);
    }

    public function test_reducing_balance_requires_rate_and_none_creates_no_schedule(): void
    {
        $reducing = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $this->category->id,
            'name' => 'Reducing balance asset',
            'depreciation_method' => FixedAssetCategory::METHOD_REDUCING_BALANCE,
            'depreciation_rate_percent' => 25,
            'useful_life_months' => 12,
            'residual_value_percent' => 10,
        ]);
        $reducing = $this->activate($reducing, 1000, '2026-01-01');
        $entries = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($reducing, $this->user);

        $this->assertNotEmpty($entries);
        $this->assertSame('25.00', $entries->first()->rate_percent_snapshot);
        $this->assertSame('100.00', $entries->last()->closing_net_book_value_gbp);
        $this->assertTrue($entries->every(fn (FixedAssetDepreciationEntry $entry): bool => $this->minor($entry->closing_net_book_value_gbp) >= 10000));

        $none = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $this->category->id,
            'name' => 'Non-depreciated asset',
            'depreciation_method' => FixedAssetCategory::METHOD_NONE,
        ]);
        $none = $this->activate($none, 100, '2026-01-02');
        $this->assertCount(0, app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($none, $this->user));
    }

    public function test_posted_depreciation_blocks_dependent_improvement_and_acquisition_reversal(): void
    {
        $asset = $this->activeAsset('Dependency guards computer', 1200, '2026-01-15');
        $acquisition = $asset->transactions->firstWhere('type', FixedAssetTransaction::TYPE_ACQUISITION);
        $entry = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user, '2026-01-31')->first();
        app(PostFixedAssetDepreciationAction::class)->execute($entry, $this->user);
        $improvementExpense = $this->postedExpense('Backdated upgrade', 100, '2026-01-20');

        try {
            app(ImproveFixedAssetAction::class)->execute($asset, $improvementExpense->items()->firstOrFail(), $this->user, '2026-01-20');
            $this->fail('An improvement covered by posted depreciation should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('Reverse depreciation', $exception->getMessage());
        }

        try {
            app(ReverseFixedAssetTransactionAction::class)->execute($acquisition, $this->user, 'Purchase cancelled', '2026-02-01');
            $this->fail('An acquisition with posted depreciation should not reverse.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('dependent fixed asset depreciation', $exception->getMessage());
        }
    }

    public function test_tenant_audit_detects_a_cross_tenant_depreciation_entry(): void
    {
        $asset = $this->activeAsset('Tenant audit computer', 1200, '2026-01-15');
        $entry = app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user, '2026-01-31')->first();
        $other = Business::create(['name' => 'Other depreciation tenant', 'slug' => 'other-depreciation-tenant', 'is_active' => true]);

        DB::table('fixed_asset_depreciation_entries')->where('id', $entry->id)->update(['business_id' => $other->id]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('fixed_asset_depreciation_entries')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    public function test_legacy_active_reducing_balance_asset_can_complete_its_missing_rate_once(): void
    {
        $asset = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $this->category->id,
            'name' => 'Legacy reducing balance asset',
            'depreciation_method' => FixedAssetCategory::METHOD_REDUCING_BALANCE,
            'depreciation_rate_percent' => 25,
            'useful_life_months' => 12,
        ]);
        $asset = $this->activate($asset, 1000, '2026-01-01');
        DB::table('fixed_assets')->where('id', $asset->id)->update(['depreciation_rate_percent' => null]);

        $asset = app(SetFixedAssetDepreciationRateAction::class)->execute($asset->fresh(), $this->user, '30.00');
        $this->assertSame('30.00', $asset->depreciation_rate_percent);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('already has an immutable depreciation rate');
        app(SetFixedAssetDepreciationRateAction::class)->execute($asset, $this->user, '35.00');
    }

    public function test_active_asset_page_shows_the_schedule_and_controlled_actions(): void
    {
        $asset = $this->activeAsset('Filament depreciation computer', 1200, '2026-01-15');
        app(GenerateFixedAssetDepreciationScheduleAction::class)->execute($asset, $this->user, '2026-01-31');

        $this->actingAs($this->user)
            ->get(FixedAssetResource::getUrl('view', ['record' => $asset]))
            ->assertOk()
            ->assertSee('Generate depreciation schedule')
            ->assertSee('Post next depreciation')
            ->assertSee('Book depreciation schedule');
    }

    private function activeAsset(string $name, float $cost, string $date): FixedAsset
    {
        $asset = app(CreateFixedAssetAction::class)->execute($this->business, $this->user, [
            'fixed_asset_category_id' => $this->category->id,
            'name' => $name,
        ]);

        return $this->activate($asset, $cost, $date);
    }

    private function activate(FixedAsset $asset, float $cost, string $date): FixedAsset
    {
        $expense = $this->postedExpense($asset->name, $cost, $date);

        return app(AcquireFixedAssetAction::class)->execute($asset, $expense->items()->firstOrFail(), $this->user, $date);
    }

    private function postedExpense(string $description, float $price, string $date): Expense
    {
        $expense = app(CreateExpenseAction::class)->execute($this->business, $this->user, [
            'supplier_id' => $this->supplier->id,
            'supplier_invoice_number' => 'DEPR-'.uniqid(),
            'issue_date' => $date,
            'tax_point' => $date,
            'business_purpose' => 'Fixed asset depreciation test',
            'items' => [[
                'description' => $description,
                'category' => 'software',
                'quantity' => 1,
                'unit_price' => $price,
                'tax_code_id' => $this->taxCode('S20')->id,
            ]],
        ]);
        $expense = app(ApproveExpenseAction::class)->execute($expense, $this->user);
        $expense = app(PostExpenseAction::class)->execute($expense, $this->user);
        FinancialAttachment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'attachable_type' => $expense->getMorphClass(),
            'attachable_id' => $expense->id,
            'uploaded_by' => $this->user->id,
            'document_type' => 'purchase_invoice',
            'disk' => 'local',
            'path' => 'tests/'.uniqid().'.pdf',
            'original_name' => 'invoice.pdf',
            'mime_type' => 'application/pdf',
            'size' => 100,
            'sha256' => hash('sha256', uniqid('', true)),
            'scan_status' => FinancialAttachment::SCAN_CLEAN,
            'retention_until' => '2033-01-01',
        ]);

        return $expense->fresh('items');
    }

    /** @return array{Invoice, InvoiceItem} */
    private function issuedInvoice(float $price, string $date): array
    {
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'INV-DEPR-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => $date,
            'tax_point' => $date,
            'due_date' => '2026-03-10',
        ]);
        $item = InvoiceItem::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => $this->taxCode('S20')->id,
            'description' => 'Fixed asset sale',
            'quantity' => 1,
            'unit_price' => $price,
        ]);

        return [app(IssueInvoiceAction::class)->execute($invoice, $this->user), $item->fresh()];
    }

    /** @param array<string, array{string, string}> $expected */
    private function assertJournalAmounts(JournalEntry $journal, array $expected): void
    {
        $journal->loadMissing('lines.ledgerAccount');

        foreach ($expected as $code => [$debit, $credit]) {
            $line = $journal->lines->firstWhere('ledgerAccount.code', $code);
            $this->assertNotNull($line, "Missing journal line for account {$code}.");
            $this->assertSame($debit, $line->debit_gbp);
            $this->assertSame($credit, $line->credit_gbp);
        }
    }

    private function minor(mixed $amount): int
    {
        return app(FixedAssetCostBasisService::class)->toMinorUnits($amount);
    }

    private function signedMinor(mixed $amount): int
    {
        $value = (string) $amount;

        return str_starts_with($value, '-') ? -$this->minor(substr($value, 1)) : $this->minor($value);
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()->where('business_id', $this->business->id)->where('code', $code)->firstOrFail();
    }
}
