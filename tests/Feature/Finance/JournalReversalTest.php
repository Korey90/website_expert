<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReverseJournalEntryAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class JournalReversalTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Reversal Ltd', 'slug' => 'reversal', 'is_active' => true]);
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
            'name' => 'January to November 2026',
            'financial_year' => 2026,
            'start_date' => '2026-01-01',
            'end_date' => '2026-11-30',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);
    }

    public function test_it_posts_an_exact_idempotent_reversal_and_marks_the_original(): void
    {
        $original = $this->postJournal([
            $this->line('1100', '120.00', '0.00', [
                'original_currency' => 'EUR',
                'original_amount' => '140.00',
                'exchange_rate' => '0.85714286',
                'memo' => 'Original bank debit',
                'dimensions' => ['department' => 'operations'],
            ]),
            $this->line('3100', '0.00', '120.00', ['memo' => 'Original equity credit']),
        ]);

        $reversal = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Correction of an incorrect opening balance',
            '2026-11-05',
        );
        $freshOriginal = $original->fresh(['reversal']);

        $this->assertSame(JournalEntry::STATUS_REVERSED, $freshOriginal->status);
        $this->assertSame($this->user->id, $freshOriginal->reversed_by);
        $this->assertNotNull($freshOriginal->reversed_at);
        $this->assertSame('Correction of an incorrect opening balance', $freshOriginal->reversal_reason);
        $this->assertTrue($freshOriginal->reversal->is($reversal));
        $this->assertSame($original->id, $reversal->reversal_of_id);
        $this->assertSame($original->getMorphClass(), $reversal->source_type);
        $this->assertSame($original->id, $reversal->source_id);
        $this->assertSame('0.00', $reversal->lines[0]->debit_gbp);
        $this->assertSame('120.00', $reversal->lines[0]->credit_gbp);
        $this->assertSame('EUR', $reversal->lines[0]->original_currency);
        $this->assertSame('140.00', $reversal->lines[0]->original_amount);
        $this->assertSame('0.85714286', $reversal->lines[0]->exchange_rate);
        $this->assertSame(['department' => 'operations'], $reversal->lines[0]->dimensions);
        $this->assertSame('120.00', $reversal->lines[1]->debit_gbp);
        $this->assertSame('0.00', $reversal->lines[1]->credit_gbp);

        $retry = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Correction of an incorrect opening balance',
            '2026-11-06',
        );

        $this->assertTrue($retry->is($reversal));
        $this->assertDatabaseCount('journal_entries', 2);
        $this->assertDatabaseCount('journal_lines', 4);
    }

    public function test_reversal_can_be_posted_to_a_later_open_period_after_original_period_is_locked(): void
    {
        $original = $this->postJournal([
            $this->line('1100', '50.00', '0.00'),
            $this->line('3100', '0.00', '50.00'),
        ]);
        $this->period->update(['status' => AccountingPeriod::STATUS_LOCKED]);
        $december = AccountingPeriod::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'name' => 'December 2026',
            'financial_year' => 2026,
            'period_number' => 12,
            'start_date' => '2026-12-01',
            'end_date' => '2026-12-31',
            'status' => AccountingPeriod::STATUS_OPEN,
            'created_by' => $this->user->id,
        ]);

        $reversal = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Late correction after period lock',
            '2026-12-05',
        );

        $this->assertSame($december->id, $reversal->accounting_period_id);
        $this->assertSame('2026-12-05', $reversal->entry_date->toDateString());
        $this->assertSame(JournalEntry::STATUS_REVERSED, $original->fresh()->status);
    }

    public function test_closed_target_period_rolls_back_the_entire_reversal(): void
    {
        $original = $this->postJournal([
            $this->line('1100', '25.00', '0.00'),
            $this->line('3100', '0.00', '25.00'),
        ]);
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Blocked reversal', '2026-11-05');
            $this->fail('A closed target period should reject a reversal.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertSame(JournalEntry::STATUS_POSTED, $original->fresh()->status);
        $this->assertNull($original->fresh()->reversed_at);
        $this->assertDatabaseCount('journal_entries', 1);
        $this->assertDatabaseCount('journal_lines', 2);
    }

    public function test_reversal_requires_permission_and_cannot_reverse_a_reversal_or_predate_original(): void
    {
        $original = $this->postJournal([
            $this->line('1100', '40.00', '0.00'),
            $this->line('3100', '0.00', '40.00'),
        ]);
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        try {
            app(ReverseJournalEntryAction::class)->execute($original, $member, 'Unauthorized correction', '2026-11-05');
            $this->fail('Posting permission should be required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot post', $exception->getMessage());
        }

        try {
            app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Predated correction', '2026-11-03');
            $this->fail('A reversal cannot predate its original.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot predate', $exception->getMessage());
        }

        $reversal = app(ReverseJournalEntryAction::class)->execute($original, $this->user, 'Valid correction', '2026-11-05');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('cannot itself be reversed');
        app(ReverseJournalEntryAction::class)->execute($reversal, $this->user, 'Invalid second reversal', '2026-11-06');
    }

    public function test_reversal_must_exactly_mirror_original_but_can_use_an_inactive_original_account(): void
    {
        $original = $this->postJournal([
            $this->line('6900', '15.00', '0.00'),
            $this->line('1100', '0.00', '15.00'),
        ]);
        $this->account('6900')->update(['is_active' => false]);

        try {
            app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
                'entry_date' => '2026-11-05',
                'description' => 'Malformed reversal',
                'idempotency_key' => 'malformed-reversal',
                'source' => $original,
                'reversal_of' => $original,
                'lines' => [
                    $this->line('6900', '0.00', '14.00'),
                    $this->line('1100', '14.00', '0.00'),
                ],
            ]);
            $this->fail('A reversal must mirror the original lines.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('exactly mirror', $exception->getMessage());
        }

        $reversal = app(ReverseJournalEntryAction::class)->execute(
            $original,
            $this->user,
            'Reverse entry after account deactivation',
            '2026-11-05',
        );

        $this->assertSame('15.00', $reversal->lines->firstWhere('ledger_account_id', $this->account('6900')->id)->credit_gbp);
        $this->assertSame(JournalEntry::STATUS_REVERSED, $original->fresh()->status);
    }

    /** @param list<array<string, mixed>> $lines */
    private function postJournal(array $lines): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute($this->business, $this->user, [
            'entry_date' => '2026-11-04',
            'description' => 'Journal reversal test',
            'idempotency_key' => 'reversal-test-'.uniqid(),
            'source_snapshot' => ['test' => true],
            'lines' => $lines,
        ]);
    }

    /** @param array<string, mixed> $extra */
    private function line(string $account, string $debit, string $credit, array $extra = []): array
    {
        return [
            'ledger_account_id' => $this->account($account)->id,
            'debit_gbp' => $debit,
            'credit_gbp' => $credit,
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
