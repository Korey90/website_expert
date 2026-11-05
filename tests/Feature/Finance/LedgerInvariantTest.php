<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\JournalLine;
use App\Models\LedgerAccount;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class LedgerInvariantTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $january;

    private AccountingPeriod $february;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Invariant Books Ltd', 'slug' => 'invariant-books', 'is_active' => true]);
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

        $this->january = $this->period('January 2026', '2026-01-01', '2026-01-31', 1);
        $this->february = $this->period('February 2026', '2026-02-01', '2026-02-28', 2);
    }

    public function test_every_posted_journal_obeys_double_entry_and_tenant_invariants(): void
    {
        $this->postJournal('2026-01-05', 'invariant:split', [
            $this->line('1100', '100.01', '0.00'),
            $this->line('4100', '0.00', '33.34'),
            $this->line('3100', '0.00', '66.67'),
        ]);
        $this->postJournal('2026-01-06', 'invariant:penny', [
            $this->line('6200', '0.01', '0.00'),
            $this->line('1100', '0.00', '0.01'),
        ]);
        $this->postJournal('2026-02-10', 'invariant:credit', [
            $this->line('2100', '25.55', '0.00'),
            $this->line('1100', '0.00', '25.55'),
        ]);

        $entries = JournalEntry::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->with(['accountingPeriod', 'lines.ledgerAccount'])
            ->get();

        $this->assertCount(3, $entries);
        $entries->each(fn (JournalEntry $entry) => $this->assertJournalInvariants($entry));
    }

    public function test_unbalanced_payload_is_rejected_without_a_partial_journal(): void
    {
        try {
            $this->postJournal('2026-01-10', 'invariant:unbalanced', [
                $this->line('1100', '10.01', '0.00'),
                $this->line('4100', '0.00', '10.00'),
            ]);
            $this->fail('An unbalanced payload must not be posted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('must balance', $exception->getMessage());
        }

        $this->assertDatabaseCount('journal_entries', 0);
        $this->assertDatabaseCount('journal_lines', 0);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('exactly one positive');
        $this->postJournal('2026-01-10', 'invariant:two-sided', [
            $this->line('1100', '10.00', '1.00'),
            $this->line('4100', '0.00', '9.00'),
        ]);
    }

    public function test_locked_or_closed_date_is_rejected_while_a_later_open_period_remains_writable(): void
    {
        foreach ([AccountingPeriod::STATUS_CLOSED, AccountingPeriod::STATUS_LOCKED] as $status) {
            $this->january->update(['status' => $status]);

            try {
                $this->postJournal('2026-01-15', "invariant:{$status}", [
                    $this->line('1100', '20.00', '0.00'),
                    $this->line('4100', '0.00', '20.00'),
                ]);
                $this->fail("A {$status} period must reject posting.");
            } catch (DomainException $exception) {
                $this->assertStringContainsString("is {$status}", $exception->getMessage());
            }
        }

        $entry = $this->postJournal('2026-02-01', 'invariant:later-open', [
            $this->line('1100', '20.00', '0.00'),
            $this->line('4100', '0.00', '20.00'),
        ]);

        $this->assertSame($this->february->id, $entry->accounting_period_id);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertJournalInvariants($entry->load(['accountingPeriod', 'lines.ledgerAccount']));
    }

    public function test_idempotency_returns_the_same_journal_and_rejects_payload_drift(): void
    {
        $payload = $this->payload('2026-01-12', 'invariant:idempotent', [
            $this->line('1100', '45.00', '0.00', ['memo' => 'Stable debit']),
            $this->line('4100', '0.00', '45.00', ['memo' => 'Stable credit']),
        ]);
        $first = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
        $retry = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);

        $this->assertTrue($first->is($retry));
        $this->assertSame($first->source_snapshot_hash, $retry->source_snapshot_hash);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);

        $payload['lines'][0]['memo'] = 'Changed debit';

        try {
            app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
            $this->fail('An idempotency key cannot be reused for changed data.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('different journal payload', $exception->getMessage());
        }

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    public function test_reversal_is_an_exact_single_mirror_and_nets_every_account_to_zero(): void
    {
        $original = $this->postJournal('2026-01-20', 'invariant:reversal-source', [
            $this->line('1100', '75.25', '0.00', [
                'original_currency' => 'EUR',
                'original_amount' => '90.00',
                'exchange_rate' => '0.83611111',
                'memo' => 'Bank dimension',
                'dimensions' => ['department' => 'operations'],
            ]),
            $this->line('4100', '0.00', '60.20'),
            $this->line('2200', '0.00', '15.05'),
        ]);
        $this->january->update(['status' => AccountingPeriod::STATUS_LOCKED]);

        $reversal = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Invariant reversal verification',
            '2026-02-02',
        );
        $original = $original->fresh(['accountingPeriod', 'lines.ledgerAccount', 'reversal']);
        $reversal->load(['accountingPeriod', 'lines.ledgerAccount']);

        $this->assertSame(JournalEntry::STATUS_REVERSED, $original->status);
        $this->assertSame($this->february->id, $reversal->accounting_period_id);
        $this->assertTrue($original->reversal->is($reversal));
        $this->assertJournalInvariants($original);
        $this->assertJournalInvariants($reversal);

        foreach ($original->lines as $index => $line) {
            $mirror = $reversal->lines[$index];
            $this->assertSame($line->ledger_account_id, $mirror->ledger_account_id);
            $this->assertSame($line->debit_gbp, $mirror->credit_gbp);
            $this->assertSame($line->credit_gbp, $mirror->debit_gbp);
            $this->assertSame($line->original_currency, $mirror->original_currency);
            $this->assertSame($line->original_amount, $mirror->original_amount);
            $this->assertSame($line->exchange_rate, $mirror->exchange_rate);
            $this->assertSame($line->dimensions, $mirror->dimensions);
        }

        $netByAccount = $original->lines->concat($reversal->lines)
            ->groupBy('ledger_account_id')
            ->map(fn ($lines): int => $lines->sum(
                fn (JournalLine $line): int => $this->minor($line->debit_gbp) - $this->minor($line->credit_gbp)
            ));
        $this->assertSame([0, 0, 0], $netByAccount->values()->all());

        $retry = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Invariant reversal verification',
            '2026-02-03',
        );
        $this->assertTrue($reversal->is($retry));
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_tenant_boundaries_reject_foreign_accounts_and_hide_foreign_journals(): void
    {
        $own = $this->postJournal('2026-01-22', 'invariant:own', [
            $this->line('1100', '12.00', '0.00'),
            $this->line('4100', '0.00', '12.00'),
        ]);
        $otherBusiness = Business::create(['name' => 'Foreign Invariant Ltd', 'slug' => 'foreign-invariant', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        $otherUser->givePermissionTo('post_finance');
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $otherPeriod = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign January 2026',
            'financial_year' => 2026,
            'period_number' => 1,
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
            'status' => AccountingPeriod::STATUS_OPEN,
        ]);
        $foreignAccount = $this->account('1100', $otherBusiness);

        try {
            $this->postJournal('2026-01-23', 'invariant:foreign-account', [
                [
                    'ledger_account_id' => $foreignAccount->id,
                    'debit_gbp' => '12.00',
                    'credit_gbp' => '0.00',
                ],
                $this->line('4100', '0.00', '12.00'),
            ]);
            $this->fail('A journal cannot use another tenant account.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('same business', $exception->getMessage());
        }

        $foreign = app(PostJournalEntryAction::class)->execute($otherBusiness, $otherUser, [
            'entry_date' => '2026-01-24',
            'description' => 'Foreign journal',
            'idempotency_key' => 'invariant:foreign',
            'lines' => [
                $this->lineFor($otherBusiness, '1100', '30.00', '0.00'),
                $this->lineFor($otherBusiness, '4100', '0.00', '30.00'),
            ],
        ]);

        $this->actingAs($this->user);
        $this->assertSame([$own->id], JournalEntry::pluck('id')->all());
        $this->assertNull(JournalEntry::find($foreign->id));
        $this->assertSame($otherPeriod->id, $foreign->accounting_period_id);
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_posted_header_and_lines_cannot_be_changed_or_deleted(): void
    {
        $entry = $this->postJournal('2026-01-25', 'invariant:immutable', [
            $this->line('1100', '18.00', '0.00'),
            $this->line('4100', '0.00', '18.00'),
        ]);

        try {
            $entry->update(['description' => 'Tampered header']);
            $this->fail('A posted journal header must be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $entry->lines->first()->update(['debit_gbp' => '19.00']);
            $this->fail('A posted journal line must be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $entry->lines->first()->delete();
            $this->fail('A posted journal line cannot be deleted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        try {
            $entry->delete();
            $this->fail('A posted journal cannot be deleted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot be deleted', $exception->getMessage());
        }

        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    private function assertJournalInvariants(JournalEntry $entry): void
    {
        $this->assertContains($entry->status, [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED]);
        $this->assertSame($entry->business_id, $entry->accountingPeriod->business_id);
        $this->assertTrue($entry->entry_date->betweenIncluded($entry->accountingPeriod->start_date, $entry->accountingPeriod->end_date));
        $this->assertGreaterThanOrEqual(2, $entry->lines->count());

        $debit = 0;
        $credit = 0;

        foreach ($entry->lines as $line) {
            $debitPennies = $this->minor($line->debit_gbp);
            $creditPennies = $this->minor($line->credit_gbp);

            $this->assertTrue(($debitPennies > 0) xor ($creditPennies > 0));
            $this->assertSame($entry->business_id, $line->business_id);
            $this->assertSame($entry->business_id, $line->ledgerAccount->business_id);
            $debit += $debitPennies;
            $credit += $creditPennies;
        }

        $this->assertSame($debit, $credit, "Journal {$entry->entry_number} is not balanced in pennies.");
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(string $date, string $key, array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute(
            $this->business,
            $this->user,
            $this->payload($date, $key, $lines),
        );
    }

    /**
     * @param  list<array<string, mixed>>  $lines
     * @return array<string, mixed>
     */
    private function payload(string $date, string $key, array $lines): array
    {
        return [
            'entry_date' => $date,
            'description' => 'Ledger invariant verification',
            'idempotency_key' => $key,
            'source_snapshot' => ['invariant' => true],
            'lines' => $lines,
        ];
    }

    /** @param array<string, mixed> $extra */
    private function line(string $account, string $debit, string $credit, array $extra = []): array
    {
        return $this->lineFor($this->business, $account, $debit, $credit, $extra);
    }

    /** @param array<string, mixed> $extra */
    private function lineFor(Business $business, string $account, string $debit, string $credit, array $extra = []): array
    {
        return [
            'ledger_account_id' => $this->account($account, $business)->id,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
            ...$extra,
        ];
    }

    private function account(string $code, ?Business $business = null): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', ($business ?? $this->business)->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function period(string $name, string $start, string $end, int $number): AccountingPeriod
    {
        return AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => $name,
            'financial_year' => 2026,
            'period_number' => $number,
            'start_date' => $start,
            'end_date' => $end,
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    private function minor(mixed $amount): int
    {
        return (int) round(((float) $amount) * 100);
    }
}
