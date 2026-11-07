<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Actions\Finance\UndoBankReconciliationAction;
use App\Filament\Resources\BankTransactionResource;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\LedgerAccount;
use App\Models\Payment;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankReconciliationUndoTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $bankAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Reconciliation Undo Ltd',
            'slug' => 'reconciliation-undo',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $cash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $this->bankAccount = app(CreateBankAccountAction::class)->execute($this->business, $this->user, [
            'name' => 'Undo operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $cash->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_undo_preserves_audit_and_releases_source_capacity(): void
    {
        $payment = $this->payment(100, 'UNDO-100', 'Undo Client');
        $transaction = $this->transaction(100, 'UNDO-100', 'Undo Client');
        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => 100,
        ]]);
        $allocationId = $reconciliation->allocations->sole()->id;

        $reversed = $this->undo($reconciliation, 'Matched to the wrong customer receipt.');

        $this->assertSame(BankReconciliation::STATUS_REVERSED, $reversed->status);
        $this->assertSame($this->user->id, $reversed->reversed_by);
        $this->assertNotNull($reversed->reversed_at);
        $this->assertSame('Matched to the wrong customer receipt.', $reversed->reversal_reason);
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->refresh()->reconciliation_status);
        $this->assertDatabaseHas('bank_reconciliation_allocations', ['id' => $allocationId]);

        $candidate = collect(app(FindBankTransactionMatchesAction::class)
            ->execute($transaction->fresh(), $this->user))
            ->firstWhere('candidateKey', "payment:{$payment->id}:receipt");

        $this->assertNotNull($candidate);
        $this->assertSame('100.00', $candidate->comparableAmount);
        $this->assertSame('100.00', $candidate->originalAmount);
    }

    public function test_undoing_one_of_multiple_batches_recalculates_partial_and_unmatched_statuses(): void
    {
        $payment = $this->payment(100, 'MULTI-100', 'Multi Client');
        $transaction = $this->transaction(100, 'MULTI-100', 'Multi Client');
        $key = "payment:{$payment->id}:receipt";
        $first = $this->reconcile($transaction, [['candidate_key' => $key, 'amount' => 40]]);
        $second = $this->reconcile($transaction, [['candidate_key' => $key, 'amount' => 60]]);

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);

        $this->undo($second, 'The second statement line belongs to another period.');

        $this->assertSame(BankTransaction::STATUS_PARTIALLY_RECONCILED, $transaction->fresh()->reconciliation_status);
        $this->assertSame('40.00', number_format((float) $transaction->reconciliationAllocations()
            ->whereHas('reconciliation', fn ($query) => $query->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount'), 2, '.', ''));
        $candidate = collect(app(FindBankTransactionMatchesAction::class)
            ->execute($transaction->fresh(), $this->user))->first();
        $this->assertSame('60.00', $candidate->comparableAmount);

        $this->undo($first, 'The remaining allocation was also incorrect.');

        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
        $this->assertSame(0, BankReconciliation::withoutGlobalScopes()
            ->where('bank_transaction_id', $transaction->id)
            ->where('status', BankReconciliation::STATUS_ACTIVE)
            ->count());
    }

    public function test_undo_keeps_all_split_allocations_as_history(): void
    {
        $firstPayment = $this->payment(60, 'UNDO-SPLIT', 'First Split Client');
        $secondPayment = $this->payment(40, 'UNDO-SPLIT', 'Second Split Client');
        $transaction = $this->transaction(100, 'UNDO-SPLIT', 'Split Client');
        $reconciliation = $this->reconcile($transaction, [
            ['candidate_key' => "payment:{$firstPayment->id}:receipt", 'amount' => 60],
            ['candidate_key' => "payment:{$secondPayment->id}:receipt", 'amount' => 40],
        ]);

        $this->undo($reconciliation, 'The split was entered against incorrect invoices.');

        $this->assertSame(2, $reconciliation->allocations()->count());
        $this->assertSame(BankTransaction::STATUS_UNMATCHED, $transaction->fresh()->reconciliation_status);
        $candidateKeys = collect(app(FindBankTransactionMatchesAction::class)
            ->execute($transaction->fresh(), $this->user))
            ->pluck('candidateKey')
            ->all();
        $this->assertContains("payment:{$firstPayment->id}:receipt", $candidateKeys);
        $this->assertContains("payment:{$secondPayment->id}:receipt", $candidateKeys);

        $replacement = $this->reconcile($transaction->fresh(), [
            ['candidate_key' => "payment:{$firstPayment->id}:receipt", 'amount' => 60],
            ['candidate_key' => "payment:{$secondPayment->id}:receipt", 'amount' => 40],
        ]);

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->refresh()->reconciliation_status);
        $this->assertCount(2, $replacement->allocations);
        $this->assertSame(4, $transaction->reconciliationAllocations()->count());
        $this->assertSame(1, $transaction->reconciliations()
            ->where('status', BankReconciliation::STATUS_REVERSED)
            ->count());
        $this->assertSame('100.00', number_format((float) $transaction->reconciliationAllocations()
            ->whereHas('reconciliation', fn ($query) => $query->where('status', BankReconciliation::STATUS_ACTIVE))
            ->sum('amount'), 2, '.', ''));
    }

    public function test_undo_is_idempotent_for_same_reason_and_history_is_immutable(): void
    {
        $payment = $this->payment(50, 'IDEMPOTENT-50', 'Idempotent Client');
        $transaction = $this->transaction(50, 'IDEMPOTENT-50', 'Idempotent Client');
        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => 50,
        ]]);
        $reason = 'Duplicate matching operation.';
        $first = $this->undo($reconciliation, $reason);
        $second = $this->undo($reconciliation, $reason);

        $this->assertSame($first->id, $second->id);
        $this->assertTrue($first->reversed_at->equalTo($second->reversed_at));

        try {
            $this->undo($reconciliation, 'A different reason.');
            $this->fail('A different retry reason must be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('different reason', $exception->getMessage());
        }

        try {
            $first->notes = 'Changed after reversal';
            $first->save();
            $this->fail('A reconciliation batch must be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $allocation = $first->allocations->sole();

        try {
            $allocation->delete();
            $this->fail('A reconciliation allocation must not be deleted.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot be deleted', $exception->getMessage());
        }

        $this->assertDatabaseHas('bank_reconciliations', [
            'id' => $first->id,
            'status' => BankReconciliation::STATUS_REVERSED,
            'reversal_reason' => $reason,
        ]);
        $this->assertDatabaseHas('bank_reconciliation_allocations', ['id' => $allocation->id]);
    }

    public function test_undo_requires_permission_and_active_tenant_membership(): void
    {
        $payment = $this->payment(30, 'SECURE-UNDO', 'Secure Undo Client');
        $transaction = $this->transaction(30, 'SECURE-UNDO', 'Secure Undo Client');
        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => 30,
        ]]);
        $member = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $member);

        try {
            app(UndoBankReconciliationAction::class)->execute($reconciliation, $member, 'No permission.');
            $this->fail('Manage finance permission is required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot reverse', $exception->getMessage());
        }

        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo('manage_finance');

        try {
            app(UndoBankReconciliationAction::class)->execute($reconciliation, $outsider, 'No membership.');
            $this->fail('Active tenant membership is required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $this->assertSame(BankReconciliation::STATUS_ACTIVE, $reconciliation->fresh()->status);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
    }

    public function test_inconsistent_batch_rolls_back_without_partial_reversal(): void
    {
        $payment = $this->payment(45, 'ROLLBACK-UNDO', 'Rollback Undo Client');
        $transaction = $this->transaction(45, 'ROLLBACK-UNDO', 'Rollback Undo Client');
        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => 45,
        ]]);
        DB::table('bank_reconciliation_allocations')
            ->where('bank_reconciliation_id', $reconciliation->id)
            ->update(['amount' => 44]);

        try {
            $this->undo($reconciliation, 'This must fail atomically.');
            $this->fail('Inconsistent audit data must stop the reversal.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('batch total', $exception->getMessage());
        }

        $fresh = $reconciliation->fresh();
        $this->assertSame(BankReconciliation::STATUS_ACTIVE, $fresh->status);
        $this->assertNull($fresh->reversed_by);
        $this->assertNull($fresh->reversed_at);
        $this->assertNull($fresh->reversal_reason);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->fresh()->reconciliation_status);
    }

    public function test_filament_shows_undo_action_and_reversal_audit_history(): void
    {
        $payment = $this->payment(75, 'UI-UNDO', 'UI Undo Client');
        $transaction = $this->transaction(75, 'UI-UNDO', 'UI Undo Client');
        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => 75,
        ]]);
        $this->actingAs($this->user);

        $this->get(BankTransactionResource::getUrl('view', ['record' => $transaction]))
            ->assertOk()
            ->assertSee(__('finance.bank_reconciliation.actions.undo'))
            ->assertSee(__('finance.bank_reconciliation.reconciliation_statuses.active'));

        $reason = 'UI audit reason for an incorrect match.';
        $this->undo($reconciliation, $reason);

        $this->get(BankTransactionResource::getUrl('view', ['record' => $transaction]))
            ->assertOk()
            ->assertDontSee(__('finance.bank_reconciliation.actions.undo'))
            ->assertSee(__('finance.bank_reconciliation.reconciliation_statuses.reversed'))
            ->assertSee($reason);
    }

    /** @param list<array{candidate_key: string, amount: int|float|string}> $allocations */
    private function reconcile(BankTransaction $transaction, array $allocations): BankReconciliation
    {
        return app(ReconcileBankTransactionAction::class)->execute($transaction, $this->user, $allocations);
    }

    private function undo(BankReconciliation $reconciliation, string $reason): BankReconciliation
    {
        return app(UndoBankReconciliationAction::class)->execute($reconciliation, $this->user, $reason);
    }

    private function transaction(float $amount, string $reference, string $counterparty): BankTransaction
    {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->bankAccount->id,
            'booking_date' => '2026-11-05',
            'amount' => $amount,
            'currency' => 'GBP',
            'reference' => $reference,
            'counterparty' => $counterparty,
            'source_hash' => hash('sha256', $reference.'|'.$amount.'|'.fake()->uuid()),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);
    }

    private function payment(float $amount, string $reference, string $company): Payment
    {
        $client = Client::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'company_name' => $company,
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'client_id' => $client->id,
            'created_by' => $this->user->id,
            'status' => 'paid',
            'currency' => 'GBP',
            'total' => $amount,
            'amount_paid' => $amount,
            'amount_due' => 0,
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
        ]);

        return Payment::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'created_by' => $this->user->id,
            'amount' => $amount,
            'amount_gbp' => $amount,
            'currency' => 'GBP',
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'reference' => $reference,
            'paid_at' => '2026-11-05 09:00:00',
        ]);
    }

    private function addMember(Business $business, User $user): void
    {
        BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }
}
