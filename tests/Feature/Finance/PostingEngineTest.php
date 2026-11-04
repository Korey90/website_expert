<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\Supplier;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PostingEngineTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Posting Ltd', 'slug' => 'posting', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        Permission::findOrCreate('post_finance', 'web');
        $this->user->givePermissionTo('post_finance');
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

    public function test_it_posts_a_balanced_journal_atomically_with_gbp_and_fx_snapshots(): void
    {
        $entry = $this->postJournal('manual:opening:1', [
            $this->debit('1100', '120.00', [
                'original_currency' => 'EUR',
                'original_amount' => '140.00',
                'exchange_rate' => '0.85714286',
                'dimensions' => ['department' => 'operations'],
            ]),
            $this->credit('3100', '120.00'),
        ]);

        $this->assertSame(JournalEntry::STATUS_POSTED, $entry->status);
        $this->assertSame('JNL-2026-000001', $entry->entry_number);
        $this->assertSame($this->user->id, $entry->posted_by);
        $this->assertNotNull($entry->posted_at);
        $this->assertSame('GBP', $entry->currency);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $entry->source_snapshot_hash);
        $this->assertCount(2, $entry->lines);
        $this->assertSame('120.00', $entry->lines[0]->debit_gbp);
        $this->assertSame('0.85714286', $entry->lines[0]->exchange_rate);
        $this->assertSame(['department' => 'operations'], $entry->lines[0]->dimensions);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    public function test_repeating_the_same_payload_is_idempotent_but_key_reuse_with_changes_is_rejected(): void
    {
        $supplier = Supplier::factory()->for($this->business)->create();
        $payload = $this->payload('supplier:'.$supplier->id, [
            $this->debit('6200', '50.00'),
            $this->credit('2100', '50.00'),
        ], [
            'source' => $supplier,
            'source_snapshot' => ['name' => $supplier->name, 'total' => '50.00'],
        ]);

        $first = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
        $retry = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);

        $this->assertTrue($first->is($retry));
        $this->assertSame($supplier->getMorphClass(), $first->source_type);
        $this->assertSame($supplier->id, $first->source_id);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);

        $payload['description'] = 'Changed retry payload';
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('different journal payload');
        app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
    }

    public function test_unbalanced_or_invalid_lines_roll_back_the_whole_transaction(): void
    {
        try {
            $this->postJournal('unbalanced:1', [
                $this->debit('1100', '100.00'),
                $this->credit('3100', '99.99'),
            ]);
            $this->fail('An unbalanced journal should not be posted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('must balance', $exception->getMessage());
        }

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_lines', 0);

        $this->expectException(DomainException::class);
        $this->postJournal('double-sided:1', [
            [
                'ledger_account_id' => $this->account('1100')->id,
                'debit_gbp' => '100.00',
                'credit_gbp' => '1.00',
            ],
            $this->credit('3100', '100.00'),
        ]);
    }

    public function test_posting_requires_a_writable_period_membership_permission_and_tenant_references(): void
    {
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            $this->postJournal('closed:1', [$this->debit('1100', '10.00'), $this->credit('3100', '10.00')]);
            $this->fail('Posting into a closed period should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->period->update(['status' => AccountingPeriod::STATUS_OPEN]);
        $unauthorized = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $unauthorized->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        try {
            app(PostJournalEntryAction::class)->execute(
                $this->business,
                $unauthorized,
                $this->payload('unauthorized:1', [$this->debit('1100', '10.00'), $this->credit('3100', '10.00')]),
            );
            $this->fail('Posting without post_finance should fail.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot post', $exception->getMessage());
        }

        $otherBusiness = Business::create(['name' => 'Foreign Posting Ltd', 'slug' => 'foreign-posting', 'is_active' => true]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness);
        $foreignAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', '1100')
            ->firstOrFail();

        $this->expectException(DomainException::class);
        $this->postJournal('foreign:1', [
            ['ledger_account_id' => $foreignAccount->id, 'debit_gbp' => '10.00', 'credit_gbp' => '0.00'],
            $this->credit('3100', '10.00'),
        ]);
    }

    public function test_posted_entries_and_lines_are_immutable_and_numbers_are_monotonic(): void
    {
        $first = $this->postJournal('immutable:1', [$this->debit('1100', '20.00'), $this->credit('3100', '20.00')]);
        $second = $this->postJournal('immutable:2', [$this->debit('1100', '30.00'), $this->credit('3100', '30.00')]);

        $this->assertSame('JNL-2026-000002', $second->entry_number);

        try {
            $first->update(['description' => 'Tampered']);
            $this->fail('A posted entry should be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $first->fresh()->lines->first()->update(['debit_gbp' => '21.00']);
            $this->fail('A posted line should be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $first->fresh()->delete();
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(string $key, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, $this->payload($key, $lines));
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(string $key, array $lines, array $overrides = []): array
    {
        return [
            'entry_date' => '2026-11-04',
            'description' => 'Posting engine test',
            'idempotency_key' => $key,
            'source_snapshot' => ['test' => true],
            'lines' => $lines,
            ...$overrides,
        ];
    }

    /** @param array<string, mixed> $extra */
    private function debit(string $account, string $amount, array $extra = []): array
    {
        return [
            'ledger_account_id' => $this->account($account)->id,
            'debit_gbp' => $amount,
            'credit_gbp' => '0.00',
            ...$extra,
        ];
    }

    /** @param array<string, mixed> $extra */
    private function credit(string $account, string $amount, array $extra = []): array
    {
        return [
            'ledger_account_id' => $this->account($account)->id,
            'debit_gbp' => '0.00',
            'credit_gbp' => $amount,
            ...$extra,
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
