<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateCreditNoteAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\IssueCreditNoteAction;
use App\Actions\Finance\IssueInvoiceAction;
use App\Actions\Finance\VoidCreditNoteAction;
use App\Models\AccountingPeriod;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\CreditNote;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\JournalEntry;
use App\Models\TaxCode;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesPostingTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private Client $client;

    private AccountingPeriod $period;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Sales Ledger Ltd', 'slug' => 'sales-ledger', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $this->client = Client::create([
            'business_id' => $this->business->id,
            'company_name' => 'Sales Customer Ltd',
            'status' => 'active',
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

    public function test_issuing_a_mixed_vat_invoice_posts_receivable_revenue_and_vat(): void
    {
        $invoice = $this->invoice();
        $this->item($invoice, 'Standard consulting', 'S20', 100);
        $this->item($invoice, 'Zero-rated service', 'Z0', 50);

        $issued = app(IssueInvoiceAction::class)->execute($invoice, $this->user);
        $journal = $issued->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame('sent', $issued->status);
        $this->assertNotNull($issued->sent_at);
        $this->assertSame(JournalEntry::STATUS_POSTED, $journal->status);
        $this->assertSame('170.00', $journal->lines->firstWhere('ledgerAccount.code', '1200')->debit_gbp);
        $this->assertSame('150.00', number_format($journal->lines->where('ledgerAccount.code', '4100')->sum('credit_gbp'), 2, '.', ''));
        $this->assertSame('20.00', $journal->lines->firstWhere('ledgerAccount.code', '2200')->credit_gbp);
        $this->assertSame($this->client->id, $journal->lines->first()->client_id);
        $this->assertCount(4, $journal->lines);

        $retry = app(IssueInvoiceAction::class)->execute($issued, $this->user);
        $this->assertTrue($retry->journalEntries()->firstOrFail()->is($journal));
        $this->assertDatabaseCount('journal_entries', 1);

        try {
            $issued->update(['total' => '999.00']);
            $this->fail('A posted invoice total should be immutable.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('immutable', $exception->getMessage());
        }

        $this->expectException(DomainException::class);
        $issued->fresh()->items->first()->update(['description' => 'Tampered item']);
    }

    public function test_foreign_currency_invoice_preserves_original_amounts_and_gbp_posting(): void
    {
        $invoice = $this->invoice([
            'currency' => 'EUR',
            'gbp_exchange_rate' => '0.85000000',
            'exchange_rate_source' => 'manual',
            'exchange_rate_date' => '2026-11-01',
        ]);
        $this->item($invoice, 'Euro consulting', 'S20', 100);

        $issued = app(IssueInvoiceAction::class)->execute($invoice, $this->user);
        $receivable = $issued->journalEntries()->firstOrFail()->lines()->whereHas(
            'ledgerAccount',
            fn ($query) => $query->where('code', '1200'),
        )->firstOrFail();

        $this->assertSame('102.00', $receivable->debit_gbp);
        $this->assertSame('EUR', $receivable->original_currency);
        $this->assertSame('120.00', $receivable->original_amount);
        $this->assertSame('0.85000000', $receivable->exchange_rate);
    }

    public function test_issuing_a_credit_note_reverses_sales_vat_and_receivable(): void
    {
        $invoice = $this->invoice();
        $invoiceItem = $this->item($invoice, 'Credited consulting', 'S20', 100);
        $invoice = app(IssueInvoiceAction::class)->execute($invoice, $this->user);
        $creditNote = app(CreateCreditNoteAction::class)->execute($invoice, $this->user, [
            'reason' => 'Service correction',
            'issue_date' => '2026-11-04',
            'tax_point' => '2026-11-04',
            'items' => [[
                'invoice_item_id' => $invoiceItem->id,
                'quantity' => 1,
            ]],
        ]);

        $issued = app(IssueCreditNoteAction::class)->execute($creditNote, $this->user);
        $journal = $issued->journalEntries()->with('lines.ledgerAccount')->firstOrFail();

        $this->assertSame(CreditNote::STATUS_ISSUED, $issued->status);
        $this->assertSame('100.00', $journal->lines->firstWhere('ledgerAccount.code', '4100')->debit_gbp);
        $this->assertSame('20.00', $journal->lines->firstWhere('ledgerAccount.code', '2200')->debit_gbp);
        $this->assertSame('120.00', $journal->lines->firstWhere('ledgerAccount.code', '1200')->credit_gbp);

        app(IssueCreditNoteAction::class)->execute($issued, $this->user);
        $this->assertDatabaseCount('journal_entries', 2);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('must be reversed');
        app(VoidCreditNoteAction::class)->execute($issued, $this->user);
    }

    public function test_closed_period_or_missing_permission_rolls_back_invoice_issue(): void
    {
        $invoice = $this->invoice();
        $this->item($invoice, 'Blocked consulting', 'S20', 100);
        $this->period->update(['status' => AccountingPeriod::STATUS_CLOSED]);

        try {
            app(IssueInvoiceAction::class)->execute($invoice, $this->user);
            $this->fail('A closed period should reject invoice issue posting.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('is closed', $exception->getMessage());
        }

        $this->assertSame('draft', $invoice->fresh()->status);
        $this->assertDatabaseCount('journal_entries', 0);

        $this->period->update(['status' => AccountingPeriod::STATUS_OPEN]);
        $unauthorized = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $unauthorized->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->expectException(DomainException::class);
        app(IssueInvoiceAction::class)->execute($invoice, $unauthorized);
    }

    /** @param array<string, mixed> $overrides */
    private function invoice(array $overrides = []): Invoice
    {
        return Invoice::create([
            'business_id' => $this->business->id,
            'number' => 'INV-SALES-'.uniqid(),
            'client_id' => $this->client->id,
            'created_by' => $this->user->id,
            'status' => 'draft',
            'currency' => 'GBP',
            'issue_date' => '2026-11-01',
            'tax_point' => '2026-11-01',
            'due_date' => '2026-12-01',
            ...$overrides,
        ]);
    }

    private function item(Invoice $invoice, string $description, string $taxCode, float $price): InvoiceItem
    {
        return InvoiceItem::create([
            'business_id' => $this->business->id,
            'invoice_id' => $invoice->id,
            'tax_code_id' => TaxCode::withoutGlobalScopes()
                ->where('business_id', $this->business->id)
                ->where('code', $taxCode)
                ->value('id'),
            'description' => $description,
            'quantity' => 1,
            'unit_price' => $price,
        ]);
    }
}
