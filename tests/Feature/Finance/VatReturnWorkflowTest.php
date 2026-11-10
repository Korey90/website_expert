<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateVatReturnReviewAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\LockVatReturnAction;
use App\Actions\Finance\PostJournalEntryAction;
use App\Actions\Finance\ReviewVatReturnAction;
use App\Actions\Finance\SupersedeVatReturnAction;
use App\Filament\Resources\VatReturnResource;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\JournalEntry;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Finance\VatReturnSnapshotService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class VatReturnWorkflowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'VAT Return Review Books',
            'slug' => 'vat-return-review-books',
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

        foreach (['access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance', 'close_finance_periods', 'post_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo([
            'access_admin_panel', 'view_finance', 'manage_finance', 'approve_finance',
            'close_finance_periods', 'post_finance',
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->business->accountingProfile()->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2020-01-01',
        ]);
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

    public function test_review_snapshot_is_deterministic_redacted_and_immutable(): void
    {
        $journal = $this->postSale('2026-03-15', 'vat-return:sale:one');
        $return = $this->createReturn();
        $snapshotJson = json_encode($return->snapshot, JSON_THROW_ON_ERROR);

        $this->assertSame('VATR-2026-001', $return->number);
        $this->assertSame(VatReturn::STATUS_DRAFT, $return->status);
        $this->assertSame(1, $return->source_journal_count);
        $this->assertSame(2, $return->source_line_count);
        $this->assertSame(20.0, (float) data_get($return->snapshot, 'calculation.boxes.0.submission_value'));
        $this->assertSame('6789', data_get($return->snapshot, 'profile.vat_number_last_four'));
        $this->assertSame(hash('sha256', 'GB123456789'), data_get($return->snapshot, 'profile.vat_number_sha256'));
        $this->assertStringNotContainsString('GB123456789', $snapshotJson);
        $this->assertSame($journal->id, data_get($return->snapshot, 'sources.rows.0.journal_entry_id'));
        $this->assertCount(2, data_get($return->snapshot, 'sources.rows.0.journal_line_ids'));
        $this->assertSame(
            $return->snapshot_hash,
            app(VatReturnSnapshotService::class)->hash($return->snapshot),
        );

        $this->assertDomainFailure(
            fn () => $return->update(['snapshot_hash' => str_repeat('0', 64)]),
            'immutable',
        );
        $this->assertDomainFailure(fn () => $return->delete(), 'cannot be deleted');
    }

    public function test_review_and_lock_require_permissions_confirmation_and_a_current_snapshot(): void
    {
        $this->postSale('2026-03-15', 'vat-return:sale:review');
        $stale = $this->createReturn();

        $this->user->revokePermissionTo('approve_finance');
        $this->assertDomainFailure(
            fn () => app(ReviewVatReturnAction::class)->execute($stale, $this->user, true),
            'cannot review',
        );
        $this->user->givePermissionTo('approve_finance');
        $this->assertDomainFailure(
            fn () => app(ReviewVatReturnAction::class)->execute($stale, $this->user, false),
            'explicitly confirm',
        );

        $this->postSale('2026-03-20', 'vat-return:sale:drift');
        $this->assertDomainFailure(
            fn () => app(ReviewVatReturnAction::class)->execute($stale, $this->user, true),
            'calculation changed',
        );

        app(SupersedeVatReturnAction::class)->execute($stale, $this->user, 'Replaced after an additional source journal was posted.');
        $return = $this->createReturn();
        $reviewed = app(ReviewVatReturnAction::class)->execute($return, $this->user, true, 'Nine boxes and source journals checked.');

        $this->assertSame(VatReturn::STATUS_REVIEWED, $reviewed->status);
        $this->assertSame($this->user->id, $reviewed->reviewed_by);
        $this->assertSame(VatReturn::REVIEW_CONFIRMATION_VERSION, $reviewed->review_confirmation_version);

        $this->user->revokePermissionTo('close_finance_periods');
        $this->assertDomainFailure(
            fn () => app(LockVatReturnAction::class)->execute($reviewed, $this->user, 'Approved quarterly VAT control reconciliation.'),
            'cannot lock',
        );
        $this->user->givePermissionTo('close_finance_periods');
        $this->assertDomainFailure(
            fn () => app(LockVatReturnAction::class)->execute($reviewed, $this->user, 'short'),
            'between 10 and 2000',
        );

        $locked = app(LockVatReturnAction::class)->execute(
            $reviewed,
            $this->user,
            'Approved quarterly VAT control reconciliation.',
        );

        $this->assertSame(VatReturn::STATUS_LOCKED, $locked->status);
        $this->assertSame($this->user->id, $locked->locked_by);
        $this->assertNotNull($locked->locked_at);
        $this->assertDomainFailure(
            fn () => app(SupersedeVatReturnAction::class)->execute($locked, $this->user, 'Attempt to replace a locked audit record.'),
            'draft or reviewed',
        );
    }

    public function test_locked_vat_range_blocks_new_journals_but_preserves_idempotent_retry(): void
    {
        $payload = $this->salePayload('2026-03-15', 'vat-return:sale:locked');
        $journal = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
        $return = app(ReviewVatReturnAction::class)->execute($this->createReturn(), $this->user, true);
        app(LockVatReturnAction::class)->execute($return, $this->user, 'Quarter reviewed and locked after source verification.');

        $retry = app(PostJournalEntryAction::class)->execute($this->business, $this->user, $payload);
        $this->assertSame($journal->id, $retry->id);
        $this->assertDatabaseCount('journal_entries', 1);

        $this->assertDomainFailure(
            fn () => app(PostJournalEntryAction::class)->execute(
                $this->business,
                $this->user,
                $this->salePayload('2026-03-31', 'vat-return:sale:blocked'),
            ),
            'locks journal date',
        );

        $outside = app(PostJournalEntryAction::class)->execute(
            $this->business,
            $this->user,
            $this->salePayload('2026-04-01', 'vat-return:sale:next-quarter'),
        );
        $this->assertSame('2026-04-01', $outside->entry_date->toDateString());
        $this->assertDatabaseCount('journal_entries', 2);
    }

    public function test_active_periods_cannot_overlap_and_superseded_reviews_can_be_replaced(): void
    {
        $first = $this->createReturn();

        $this->assertDomainFailure(
            fn () => app(CreateVatReturnReviewAction::class)->execute(
                $this->business,
                $this->user,
                '2026-03-01',
                '2026-04-30',
            ),
            'overlapping active period',
        );

        $superseded = app(SupersedeVatReturnAction::class)->execute(
            $first,
            $this->user,
            'The reviewer requested a regenerated source snapshot.',
        );
        $replacement = $this->createReturn();

        $this->assertSame(VatReturn::STATUS_SUPERSEDED, $superseded->status);
        $this->assertSame('VATR-2026-002', $replacement->number);
        $this->assertSame(VatReturn::STATUS_DRAFT, $replacement->status);
    }

    public function test_filament_pages_and_tenant_audit_enforce_permissions_and_snapshot_ownership(): void
    {
        $this->postSale('2026-03-15', 'vat-return:sale:audit');
        $return = $this->createReturn();

        $this->actingAs($this->user)
            ->get(VatReturnResource::getUrl('index'))
            ->assertOk()
            ->assertSee($return->number);
        $this->get(VatReturnResource::getUrl('view', ['record' => $return]))
            ->assertOk()
            ->assertSee($return->snapshot_hash);

        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $member->givePermissionTo('access_admin_panel');
        $this->actingAs($member)->get(VatReturnResource::getUrl('index'))->assertForbidden();

        $this->actingAs($this->user)
            ->artisan('finance:audit-tenants')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);

        $otherBusiness = Business::create(['name' => 'Other VAT Review Books', 'slug' => 'other-vat-review-books', 'is_active' => true]);
        DB::table('vat_returns')->where('id', $return->id)->update(['business_id' => $otherBusiness->id]);

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('vat_returns.snapshot_sources')
            ->expectsOutputToContain('Finance tenant audit failed: 1 invalid record(s).')
            ->assertExitCode(1);
    }

    private function createReturn(): VatReturn
    {
        return app(CreateVatReturnReviewAction::class)->execute(
            $this->business,
            $this->user,
            '2026-01-01',
            '2026-03-31',
        );
    }

    private function postSale(string $date, string $idempotencyKey): JournalEntry
    {
        return app(PostJournalEntryAction::class)->execute(
            $this->business,
            $this->user,
            $this->salePayload($date, $idempotencyKey),
        );
    }

    /** @return array<string, mixed> */
    private function salePayload(string $date, string $idempotencyKey): array
    {
        return [
            'entry_date' => $date,
            'description' => 'Standard-rated sale for VAT Return review',
            'idempotency_key' => $idempotencyKey,
            'source_snapshot' => ['test' => true],
            'lines' => [
                ['ledger_account_id' => $this->account('1200')->id, 'debit_gbp' => '120.00', 'credit_gbp' => '0.00'],
                ['ledger_account_id' => $this->account('4100')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '100.00'],
                ['ledger_account_id' => $this->account('2200')->id, 'tax_code_id' => $this->taxCode('S20')->id, 'debit_gbp' => '0.00', 'credit_gbp' => '20.00'],
            ],
        ];
    }

    private function account(string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function taxCode(string $code): TaxCode
    {
        return TaxCode::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', $code)
            ->firstOrFail();
    }

    private function assertDomainFailure(callable $callback, string $message): void
    {
        try {
            $callback();
            $this->fail('Expected a DomainException to be thrown.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString($message, $exception->getMessage());
        }
    }
}
