<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\FindBankTransactionMatchesAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\ReconcileBankTransactionAction;
use App\Filament\Resources\BankTransactionResource;
use App\Models\BankAccount;
use App\Models\BankReconciliation;
use App\Models\BankReconciliationAllocation;
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
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankReconciliationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Reconciliation Ltd',
            'slug' => 'reconciliation',
            'is_active' => true,
        ]);
        $this->user = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $this->user);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
        $cashAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $this->account = app(CreateBankAccountAction::class)->execute($this->business, $this->user, [
            'name' => 'Operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $cashAccount->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
    }

    public function test_exact_reconciliation_creates_audited_allocation_and_completes_transaction(): void
    {
        $payment = $this->payment(100, 'EXACT-100', 'Exact Client');
        $transaction = $this->transaction(100, 'EXACT-100', 'Exact Client');

        $reconciliation = $this->reconcile($transaction, [
            ['candidate_key' => "payment:{$payment->id}:receipt", 'amount' => '100.00'],
        ], 'Exact bank match');

        $transaction->refresh();
        $allocation = $reconciliation->allocations->sole();
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->reconciliation_status);
        $this->assertSame($this->business->id, $reconciliation->business_id);
        $this->assertSame($this->user->id, $reconciliation->created_by);
        $this->assertSame(BankReconciliation::STATUS_ACTIVE, $reconciliation->status);
        $this->assertSame('100.00', $reconciliation->total_amount);
        $this->assertSame(Payment::class, $allocation->reconcilable_type);
        $this->assertSame($payment->id, $allocation->reconcilable_id);
        $this->assertSame('customer_payment', $allocation->event_type);
        $this->assertSame('100.00', $allocation->amount);
        $this->assertSame('100.00', $allocation->source_amount);
        $this->assertSame(100, $allocation->match_score);
    }

    public function test_partial_transaction_can_be_completed_in_a_second_audited_batch(): void
    {
        $payment = $this->payment(100, 'PARTIAL-100', 'Partial Client');
        $transaction = $this->transaction(100, 'PARTIAL-100', 'Partial Client');
        $key = "payment:{$payment->id}:receipt";

        $first = $this->reconcile($transaction, [['candidate_key' => $key, 'amount' => 40]]);
        $transaction->refresh();
        $remainingCandidate = app(FindBankTransactionMatchesAction::class)
            ->execute($transaction, $this->user)[0];

        $this->assertSame(BankTransaction::STATUS_PARTIALLY_RECONCILED, $transaction->reconciliation_status);
        $this->assertSame('40.00', $first->total_amount);
        $this->assertSame('60.00', $remainingCandidate->comparableAmount);
        $this->assertSame('60.00', $remainingCandidate->originalAmount);

        $second = $this->reconcile($transaction, [['candidate_key' => $key, 'amount' => 60]]);

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->refresh()->reconciliation_status);
        $this->assertSame('60.00', $second->total_amount);
        $this->assertSame(2, $transaction->reconciliations()->count());
        $this->assertSame('100.00', number_format((float) $transaction->reconciliationAllocations()->sum('amount'), 2, '.', ''));
    }

    public function test_split_reconciliation_allocates_one_transaction_to_multiple_sources(): void
    {
        $firstPayment = $this->payment(60, 'SPLIT-100', 'Split Client');
        $secondPayment = $this->payment(40, 'SPLIT-100', 'Split Client');
        $transaction = $this->transaction(100, 'SPLIT-100', 'Split Client');

        $reconciliation = $this->reconcile($transaction, [
            ['candidate_key' => "payment:{$firstPayment->id}:receipt", 'amount' => 60],
            ['candidate_key' => "payment:{$secondPayment->id}:receipt", 'amount' => 40],
        ]);

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->refresh()->reconciliation_status);
        $this->assertCount(2, $reconciliation->allocations);
        $this->assertEqualsCanonicalizing(
            [$firstPayment->id, $secondPayment->id],
            $reconciliation->allocations->pluck('reconcilable_id')->all(),
        );
        $this->assertSame('100.00', $reconciliation->total_amount);
    }

    public function test_many_to_many_reconciliation_tracks_source_capacity_and_rejects_overallocation(): void
    {
        $payment = $this->payment(100, 'MANY-100', 'Many Client');
        $firstTransaction = $this->transaction(60, 'MANY-100', 'Many Client');
        $secondTransaction = $this->transaction(40, 'MANY-100', 'Many Client');
        $key = "payment:{$payment->id}:receipt";

        $this->reconcile($firstTransaction, [['candidate_key' => $key, 'amount' => 60]]);

        try {
            $this->reconcile($secondTransaction, [['candidate_key' => $key, 'amount' => 50]]);
            $this->fail('A source or transaction must not be over-allocated.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('remaining source amount', $exception->getMessage());
        }

        $this->reconcile($secondTransaction, [['candidate_key' => $key, 'amount' => 40]]);

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $firstTransaction->refresh()->reconciliation_status);
        $this->assertSame(BankTransaction::STATUS_RECONCILED, $secondTransaction->refresh()->reconciliation_status);
        $this->assertSame('100.00', number_format((float) BankReconciliationAllocation::withoutGlobalScopes()
            ->where('reconcilable_type', Payment::class)
            ->where('reconcilable_id', $payment->id)
            ->where('event_type', 'customer_payment')
            ->sum('source_amount'), 2, '.', ''));
    }

    public function test_gbp_allocation_preserves_foreign_source_amount_from_existing_snapshot(): void
    {
        $payment = $this->payment(100, 'FX-100', 'FX Client', [
            'currency' => 'EUR',
            'amount_gbp' => 85,
            'gbp_exchange_rate' => 0.85,
        ]);
        $transaction = $this->transaction(42.50, 'FX-100', 'FX Client');

        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:receipt",
            'amount' => '42.50',
        ]]);
        $allocation = $reconciliation->allocations->sole();

        $this->assertSame('42.50', $allocation->amount);
        $this->assertSame('GBP', $allocation->currency);
        $this->assertSame('50.00', $allocation->source_amount);
        $this->assertSame('EUR', $allocation->source_currency);
    }

    public function test_outflow_reconciliation_uses_positive_allocation_for_customer_refund(): void
    {
        $payment = $this->payment(35, 'REFUND-35', 'Refund Client', [
            'status' => 'refunded',
            'refunded_at' => '2026-11-05 12:00:00',
        ]);
        $transaction = $this->transaction(-35, 'REFUND-35', 'Refund Client');

        $reconciliation = $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$payment->id}:refund",
            'amount' => 35,
        ]]);
        $allocation = $reconciliation->allocations->sole();

        $this->assertSame(BankTransaction::STATUS_RECONCILED, $transaction->refresh()->reconciliation_status);
        $this->assertSame('customer_refund', $allocation->event_type);
        $this->assertSame('35.00', $allocation->amount);
        $this->assertSame('35.00', $allocation->source_amount);
    }

    public function test_action_enforces_permission_membership_and_tenant_source_boundary(): void
    {
        $payment = $this->payment(50, 'SECURE-50', 'Secure Client');
        $transaction = $this->transaction(50, 'SECURE-50', 'Secure Client');
        $key = "payment:{$payment->id}:receipt";
        $member = User::factory()->create(['is_active' => true]);
        $this->addMember($this->business, $member);

        try {
            app(ReconcileBankTransactionAction::class)->execute($transaction, $member, [['candidate_key' => $key, 'amount' => 50]]);
            $this->fail('Finance management permission is required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot reconcile', $exception->getMessage());
        }

        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo('manage_finance');

        try {
            app(ReconcileBankTransactionAction::class)->execute($transaction, $outsider, [['candidate_key' => $key, 'amount' => 50]]);
            $this->fail('Membership in the transaction tenant is required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $otherBusiness = Business::create(['name' => 'Foreign Ltd', 'slug' => 'foreign-reconcile', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        $this->addMember($otherBusiness, $otherUser);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $foreignPayment = $this->payment(50, 'SECURE-50', 'Foreign Client', [], $otherBusiness, $otherUser);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('does not belong to this business');
        $this->reconcile($transaction, [[
            'candidate_key' => "payment:{$foreignPayment->id}:receipt",
            'amount' => 50,
        ]]);
    }

    public function test_authorized_filament_resource_lists_and_shows_tenant_transaction(): void
    {
        $transaction = $this->transaction(75, 'UI-75', 'UI Client');

        $this->actingAs($this->user);
        $this->assertTrue(Gate::forUser($this->user)->allows('view', $transaction));
        $this->get(BankTransactionResource::getUrl('index'))
            ->assertOk()
            ->assertSee('UI Client')
            ->assertSee('UI-75');
        $this->get(BankTransactionResource::getUrl('view', ['record' => $transaction]))
            ->assertOk()
            ->assertSee('UI Client')
            ->assertSee('UI-75');
    }

    /** @param list<array{candidate_key: string, amount: int|float|string}> $allocations */
    private function reconcile(BankTransaction $transaction, array $allocations, ?string $notes = null): BankReconciliation
    {
        return app(ReconcileBankTransactionAction::class)->execute($transaction, $this->user, $allocations, $notes);
    }

    /** @param array<string, mixed> $overrides */
    private function transaction(float $amount, string $reference, string $counterparty, array $overrides = []): BankTransaction
    {
        return BankTransaction::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'bank_account_id' => $this->account->id,
            'booking_date' => '2026-11-05',
            'amount' => $amount,
            'currency' => 'GBP',
            'reference' => $reference,
            'counterparty' => $counterparty,
            'source_hash' => hash('sha256', $reference.'|'.$amount.'|'.fake()->uuid()),
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
            ...$overrides,
        ]);
    }

    /** @param array<string, mixed> $overrides */
    private function payment(
        float $amount,
        string $reference,
        string $company,
        array $overrides = [],
        ?Business $business = null,
        ?User $creator = null,
    ): Payment {
        $business ??= $this->business;
        $creator ??= $this->user;
        $currency = $overrides['currency'] ?? 'GBP';
        $client = Client::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'company_name' => $company,
            'status' => 'active',
        ]);
        $invoice = Invoice::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'client_id' => $client->id,
            'created_by' => $creator->id,
            'status' => 'paid',
            'currency' => $currency,
            'total' => $amount,
            'amount_paid' => $amount,
            'amount_due' => 0,
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-11-30',
        ]);

        return Payment::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'invoice_id' => $invoice->id,
            'created_by' => $creator->id,
            'amount' => $amount,
            'amount_gbp' => $amount,
            'currency' => $currency,
            'gbp_exchange_rate' => 1,
            'exchange_rate_source' => 'identity',
            'exchange_rate_date' => '2026-11-05',
            'method' => 'bank_transfer',
            'status' => 'completed',
            'reference' => $reference,
            'paid_at' => '2026-11-05 09:00:00',
            ...$overrides,
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
