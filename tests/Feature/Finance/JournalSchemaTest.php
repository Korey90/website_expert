<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\User;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class JournalSchemaTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Journal Ltd', 'slug' => 'journal', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
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

    public function test_journal_entry_stores_ordered_double_entry_lines_and_dimensions(): void
    {
        $entry = $this->entry('sale:1');
        $debit = JournalLine::create([
            'business_id' => $this->business->id,
            'journal_entry_id' => $entry->id,
            'ledger_account_id' => $this->account('1100')->id,
            'line_number' => 1,
            'debit_gbp' => 120,
            'credit_gbp' => 0,
            'original_currency' => 'EUR',
            'original_amount' => 140,
            'exchange_rate' => 0.85714286,
            'dimensions' => ['department' => 'web'],
        ]);
        JournalLine::create([
            'business_id' => $this->business->id,
            'journal_entry_id' => $entry->id,
            'ledger_account_id' => $this->account('4100')->id,
            'line_number' => 2,
            'debit_gbp' => 0,
            'credit_gbp' => 120,
        ]);

        $this->assertSame(['1', '2'], $entry->lines()->pluck('line_number')->map(fn (int $number): string => (string) $number)->all());
        $this->assertSame('120.00', $debit->debit_gbp);
        $this->assertSame('0.85714286', $debit->exchange_rate);
        $this->assertSame(['department' => 'web'], $debit->dimensions);
        $this->assertTrue($debit->journalEntry->is($entry));
        $this->assertTrue($entry->accountingPeriod->is($this->period));
    }

    public function test_database_rejects_a_line_with_both_or_neither_side_populated(): void
    {
        $entry = $this->entry('invalid-line:1');
        $base = [
            'business_id' => $this->business->id,
            'journal_entry_id' => $entry->id,
            'ledger_account_id' => $this->account('1100')->id,
            'line_number' => 1,
            'original_currency' => 'GBP',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        try {
            DB::table('journal_lines')->insert([...$base, 'debit_gbp' => 10, 'credit_gbp' => 10]);
            $this->fail('A journal line cannot contain both a debit and a credit.');
        } catch (QueryException $exception) {
            $this->assertStringContainsString('exactly one positive', $exception->getMessage());
        }

        $this->expectException(QueryException::class);
        DB::table('journal_lines')->insert([...$base, 'debit_gbp' => 0, 'credit_gbp' => 0]);
    }

    public function test_journal_rejects_cross_tenant_periods_and_accounts(): void
    {
        $otherBusiness = Business::create(['name' => 'Other Journal Ltd', 'slug' => 'other-journal', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $otherPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other FY',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
        ]);

        try {
            JournalEntry::create([
                'business_id' => $this->business->id,
                'accounting_period_id' => $otherPeriod->id,
                'entry_date' => '2026-11-04',
                'description' => 'Cross tenant period',
                'idempotency_key' => 'cross-period',
            ]);
            $this->fail('A journal cannot use another tenant accounting period.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('period', $exception->getMessage());
        }

        $entry = $this->entry('cross-account');
        $foreignAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', '1100')
            ->firstOrFail();

        $this->expectException(DomainException::class);
        JournalLine::create([
            'business_id' => $this->business->id,
            'journal_entry_id' => $entry->id,
            'ledger_account_id' => $foreignAccount->id,
            'line_number' => 1,
            'debit_gbp' => 1,
            'credit_gbp' => 0,
        ]);
    }

    public function test_journal_date_must_fall_inside_the_selected_period(): void
    {
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('must fall within');

        JournalEntry::create([
            'business_id' => $this->business->id,
            'accounting_period_id' => $this->period->id,
            'entry_date' => '2027-01-01',
            'description' => 'Outside period',
            'idempotency_key' => 'outside-period',
        ]);
    }

    public function test_idempotency_keys_are_unique_per_tenant_and_queries_are_isolated(): void
    {
        $entry = $this->entry('source:42');
        $otherBusiness = Business::create(['name' => 'Independent Ltd', 'slug' => 'independent-journal', 'is_active' => true]);
        $otherPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'FY 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
        ]);
        JournalEntry::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'accounting_period_id' => $otherPeriod->id,
            'entry_date' => '2026-11-04',
            'description' => 'Same source in another tenant',
            'idempotency_key' => 'source:42',
        ]);

        $this->actingAs($this->user);
        $this->assertSame(1, JournalEntry::count());
        $this->assertTrue(JournalEntry::first()->is($entry));

        $this->expectException(QueryException::class);
        $this->entry('source:42');
    }

    private function entry(string $idempotencyKey): JournalEntry
    {
        return JournalEntry::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'accounting_period_id' => $this->period->id,
            'entry_date' => '2026-11-04',
            'description' => 'Test journal entry',
            'idempotency_key' => $idempotencyKey,
            'source_snapshot_hash' => hash('sha256', $idempotencyKey),
        ]);
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
