<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Filament\Pages\GeneralLedgerPage;
use App\Filament\Resources\JournalEntryResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\GeneralLedgerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class GeneralLedgerViewerTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'General Ledger Ltd', 'slug' => 'general-ledger', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'post_finance']);

        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_general_ledger_calculates_opening_and_running_balances_in_pennies(): void
    {
        $this->postJournal('2026-01-10', 'opening:1', 'Opening capital', [
            $this->line('1100', '100.01', '0.00'),
            $this->line('3100', '0.00', '100.01'),
        ]);
        $this->postJournal('2026-02-10', 'purchase:1', 'Software payment', [
            $this->line('6200', '40.00', '0.00'),
            $this->line('1100', '0.00', '40.00'),
        ]);

        $bank = $this->account('1100');
        $report = app(GeneralLedgerService::class)->report(
            $this->business->id,
            '2026-02-01',
            '2026-02-28',
            $bank->id,
        );
        $account = $report['accounts']->sole();

        $this->assertSame('1100', $account->code);
        $this->assertSame(100.01, $account->opening_balance->amount);
        $this->assertSame('debit', $account->opening_balance->side);
        $this->assertSame(60.01, $account->closing_balance->amount);
        $this->assertSame('debit', $account->closing_balance->side);
        $this->assertSame(60.01, $account->rows->sole()->balance->amount);
        $this->assertSame(0.0, $account->debit_gbp);
        $this->assertSame(40.0, $account->credit_gbp);
        $this->assertSame(-40.0, $report['totals']->difference_gbp);
    }

    public function test_report_is_balanced_tenant_isolated_and_can_filter_an_account(): void
    {
        $this->postJournal('2026-03-01', 'sale:1', 'Cash sale', [
            $this->line('1100', '120.00', '0.00'),
            $this->line('4100', '0.00', '120.00'),
        ]);
        $otherBusiness = Business::create(['name' => 'Private Ledger Ltd', 'slug' => 'private-ledger', 'is_active' => true]);
        $otherUser = User::factory()->create();
        BusinessUser::create(['business_id' => $otherBusiness->id, 'user_id' => $otherUser->id, 'role' => 'owner', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $otherUser->givePermissionTo('post_finance');
        AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
        ]);
        app(PostJournalEntryAction::class)->execute($otherBusiness, $otherUser, [
            'entry_date' => '2026-03-01',
            'description' => 'Private entry',
            'idempotency_key' => 'private:1',
            'lines' => [
                $this->lineFor($otherBusiness, '1100', '999.00', '0.00'),
                $this->lineFor($otherBusiness, '4100', '0.00', '999.00'),
            ],
        ]);

        $report = app(GeneralLedgerService::class)->report($this->business->id, '2026-03-01', '2026-03-31');

        $this->assertSame(120.0, $report['totals']->debit_gbp);
        $this->assertSame(120.0, $report['totals']->credit_gbp);
        $this->assertSame(0.0, $report['totals']->difference_gbp);
        $this->assertSame(2, $report['totals']->line_count);
        $this->assertEqualsCanonicalizing(['1100', '4100'], $report['accounts']->pluck('code')->all());

        $filtered = app(GeneralLedgerService::class)->report($this->business->id, '2026-03-01', '2026-03-31', $this->account('4100')->id);
        $this->assertSame(['4100'], $filtered['accounts']->pluck('code')->all());
        $this->assertSame('credit', $filtered['accounts']->sole()->closing_balance->side);
    }

    public function test_reversed_original_and_reversal_both_remain_in_the_ledger_history(): void
    {
        $original = $this->postJournal('2026-04-01', 'mistake:1', 'Incorrect opening balance', [
            $this->line('1100', '50.00', '0.00'),
            $this->line('3100', '0.00', '50.00'),
        ]);
        app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Remove incorrect balance', '2026-04-02');

        $report = app(GeneralLedgerService::class)->report(
            $this->business->id,
            '2026-04-01',
            '2026-04-30',
            $this->account('1100')->id,
        );
        $account = $report['accounts']->sole();

        $this->assertCount(2, $account->rows);
        $this->assertSame(['reversed', 'posted'], $account->rows->pluck('status')->all());
        $this->assertSame(0.0, $account->closing_balance->amount);
        $this->assertSame(50.0, $account->debit_gbp);
        $this->assertSame(50.0, $account->credit_gbp);
    }

    public function test_report_rejects_invalid_dates_and_cross_tenant_account_filters(): void
    {
        try {
            app(GeneralLedgerService::class)->report($this->business->id, '2026-02-01', '2026-01-01');
            $this->fail('An invalid date range should fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('cannot precede', $exception->getMessage());
        }

        $otherBusiness = Business::create(['name' => 'Other Account Ltd', 'slug' => 'other-account', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignAccount = LedgerAccount::withoutGlobalScopes()->where('business_id', $otherBusiness->id)->where('code', '1100')->firstOrFail();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('does not belong');
        app(GeneralLedgerService::class)->report($this->business->id, '2026-01-01', '2026-12-31', $foreignAccount->id);
    }

    public function test_authorized_user_can_open_read_only_general_ledger_and_journal_pages(): void
    {
        $entry = $this->postJournal('2026-11-01', 'viewer:1', 'Journal viewer entry', [
            $this->line('1100', '75.00', '0.00'),
            $this->line('4100', '0.00', '75.00'),
        ]);

        $this->actingAs($this->user)
            ->get(GeneralLedgerPage::getUrl())
            ->assertOk()
            ->assertSee(__('finance.general_ledger.title'))
            ->assertSee($entry->entry_number);

        $this->get(JournalEntryResource::getUrl('index'))
            ->assertOk()
            ->assertSee($entry->entry_number);
        $this->get(JournalEntryResource::getUrl('view', ['record' => $entry]))
            ->assertOk()
            ->assertSee('Journal viewer entry')
            ->assertSee('1100');

        $this->assertFalse(JournalEntryResource::canCreate());
        $this->assertFalse(JournalEntryResource::canEdit($entry));
        $this->assertFalse(JournalEntryResource::canDelete($entry));
        $this->assertFalse(JournalEntryResource::canDeleteAny());
    }

    public function test_finance_permission_and_tenant_scope_protect_the_viewers(): void
    {
        $entry = $this->postJournal('2026-11-02', 'protected:1', 'Protected journal', [
            $this->line('1100', '10.00', '0.00'),
            $this->line('4100', '0.00', '10.00'),
        ]);
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $member->id, 'role' => 'member', 'is_active' => true]);
        $member->givePermissionTo('access_admin_panel');

        $this->actingAs($member)->get(GeneralLedgerPage::getUrl())->assertForbidden();
        $this->get(JournalEntryResource::getUrl('index'))->assertForbidden();

        $otherBusiness = Business::create(['name' => 'Viewer Other Ltd', 'slug' => 'viewer-other', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        BusinessUser::create(['business_id' => $otherBusiness->id, 'user_id' => $otherUser->id, 'role' => 'owner', 'is_active' => true]);
        $otherUser->givePermissionTo(['access_admin_panel', 'view_finance']);

        $this->actingAs($otherUser)
            ->get(JournalEntryResource::getUrl('view', ['record' => $entry]))
            ->assertNotFound();
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(string $date, string $key, string $description, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => $date,
            'description' => $description,
            'idempotency_key' => $key,
            'source_snapshot' => ['test' => true],
            'lines' => $lines,
        ]);
    }

    /** @return array<string, mixed> */
    private function line(string $account, string $debit, string $credit): array
    {
        return $this->lineFor($this->business, $account, $debit, $credit);
    }

    /** @return array<string, mixed> */
    private function lineFor(Business $business, string $account, string $debit, string $credit): array
    {
        return [
            'ledger_account_id' => LedgerAccount::withoutGlobalScopes()
                ->where('business_id', $business->id)
                ->where('code', $account)
                ->value('id'),
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
        ];
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
