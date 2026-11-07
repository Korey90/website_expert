<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\ImportBankStatementCsvAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Data\Finance\BankStatementImportResult;
use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\BankStatementImport;
use App\Models\BankTransaction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\LedgerAccount;
use App\Models\User;
use App\Services\Finance\BankStatementCsvParser;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankStatementCsvImportTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private BankAccount $account;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->business = Business::create(['name' => 'Import Ltd', 'slug' => 'import-ltd', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
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

    public function test_parser_detects_uk_csv_format_and_reports_invalid_rows_without_raw_storage(): void
    {
        $path = $this->statementFile();
        $result = app(BankStatementCsvParser::class)->parse($path, [], [
            'delimiter' => 'auto',
            'date_format' => 'auto',
            'decimal_separator' => 'comma',
            'default_currency' => 'GBP',
        ]);

        $this->assertSame(3, $result->totalRows);
        $this->assertSame(2, $result->validRows());
        $this->assertSame(1, $result->failedRows());
        $this->assertSame(4, $result->errors[0]->rowNumber);
        $this->assertSame('2026-11-05', $result->rows[0]->bookingDate);
        $this->assertSame('-12.34', $result->rows[0]->amount);
        $this->assertSame('1000.50', $result->rows[1]->amount);
        $this->assertSame('tx-2', $result->rows[1]->providerId);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $result->rows[0]->sourceHash);
        $this->assertFalse(collect(DB::getSchemaBuilder()->getColumnListing('bank_transactions'))->contains('raw_payload'));
    }

    public function test_custom_column_mapping_and_number_formats_are_supported(): void
    {
        $path = $this->putCsv('custom.csv', "Booked,Value,Note\n2026-11-05,\"1,234.56\",Invoice 42\n");
        $result = app(BankStatementCsvParser::class)->parse($path, [
            'booking_date' => 'Booked',
            'amount' => 'Value',
            'reference' => 'Note',
        ], [
            'delimiter' => 'comma',
            'date_format' => 'Y-m-d',
            'decimal_separator' => 'dot',
            'default_currency' => 'GBP',
        ]);

        $this->assertSame(1, $result->validRows());
        $this->assertSame('1234.56', $result->rows[0]->amount);
        $this->assertSame('Invoice 42', $result->rows[0]->reference);
    }

    public function test_dry_run_does_not_write_imports_transactions_or_sync_metadata(): void
    {
        $result = $this->import($this->statementFile(), true);

        $this->assertTrue($result->dryRun);
        $this->assertSame(0, $result->importedRows);
        $this->assertSame(0, $result->duplicateRows);
        $this->assertDatabaseCount('bank_statement_imports', 0);
        $this->assertDatabaseCount('bank_transactions', 0);
        $this->assertNull($this->account->fresh()->last_synced_at);
    }

    public function test_commit_imports_valid_rows_and_audits_the_batch(): void
    {
        $result = $this->import($this->statementFile(), false);

        $this->assertFalse($result->dryRun);
        $this->assertSame(2, $result->importedRows);
        $this->assertSame(0, $result->duplicateRows);
        $this->assertNotNull($result->importId);
        $this->assertDatabaseHas('bank_statement_imports', [
            'id' => $result->importId,
            'business_id' => $this->business->id,
            'bank_account_id' => $this->account->id,
            'status' => BankStatementImport::STATUS_COMPLETED_WITH_ERRORS,
            'total_rows' => 3,
            'valid_rows' => 2,
            'imported_rows' => 2,
            'duplicate_rows' => 0,
            'failed_rows' => 1,
            'imported_by' => $this->user->id,
        ]);
        $this->assertDatabaseHas('bank_transactions', [
            'business_id' => $this->business->id,
            'bank_account_id' => $this->account->id,
            'provider_id' => 'tx-1',
            'booking_date' => '2026-11-05',
            'amount' => -12.34,
            'currency' => 'GBP',
            'reference' => 'Hosting',
            'counterparty' => 'Vendor',
            'reconciliation_status' => BankTransaction::STATUS_UNMATCHED,
        ]);
        $this->assertNotNull($this->account->fresh()->last_synced_at);
        $this->assertSame(2, $this->account->transactions()->count());
        $this->assertSame(2, $this->business->bankTransactions()->count());
    }

    public function test_provider_id_and_canonical_hash_prevent_duplicates_per_account(): void
    {
        $this->import($this->statementFile(), false);
        $sameProvider = $this->putCsv('provider.csv', "date,amount,transaction id,description\n07/11/2026,99.00,tx-1,Changed\n");
        $providerResult = $this->import($sameProvider, false, ['decimal_separator' => 'dot']);

        $this->assertSame(0, $providerResult->importedRows);
        $this->assertSame(1, $providerResult->duplicateRows);

        $noProvider = $this->putCsv('hash.csv', "date,amount,description\n08/11/2026,15.00,Bank fee\n08/11/2026,15.00,Bank fee\n");
        $hashResult = $this->import($noProvider, false, ['decimal_separator' => 'dot']);

        $this->assertSame(1, $hashResult->importedRows);
        $this->assertSame(1, $hashResult->duplicateRows);
        $this->assertDatabaseCount('bank_transactions', 3);

        $preview = $this->import($this->statementFile(), true);
        $this->assertSame(2, $preview->duplicateRows);
        $this->assertDatabaseCount('bank_statement_imports', 3);
    }

    public function test_duplicate_identity_is_scoped_to_bank_account_and_tenant(): void
    {
        $cashAccount = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $this->business->id)
            ->where('code', '1100')
            ->firstOrFail();
        $reserveAccount = app(CreateBankAccountAction::class)->execute($this->business, $this->user, [
            'name' => 'Reserve account',
            'bank_name' => 'Example Bank',
            'account_number' => '87654321',
            'sort_code' => '65-43-21',
            'currency' => 'GBP',
            'ledger_account_id' => $cashAccount->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
        $otherBusiness = Business::create([
            'name' => 'Other Import Ltd',
            'slug' => 'other-import-ltd',
            'is_active' => true,
        ]);
        $otherUser = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $otherUser->givePermissionTo('manage_finance');
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $otherCash = LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $otherBusiness->id)
            ->where('code', '1100')
            ->firstOrFail();
        $otherAccount = app(CreateBankAccountAction::class)->execute($otherBusiness, $otherUser, [
            'name' => 'Other tenant account',
            'bank_name' => 'Example Bank',
            'account_number' => '22223333',
            'sort_code' => '22-33-44',
            'currency' => 'GBP',
            'ledger_account_id' => $otherCash->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
        ]);
        $path = $this->putCsv(
            'scoped-duplicate.csv',
            "date,amount,transaction id,description\n05/11/2026,10.00,SCOPE-1,Scoped transfer\n",
        );
        $options = ['decimal_separator' => 'dot'];

        $first = $this->import($path, false, $options);
        $secondAccount = $this->import($path, false, $options, $reserveAccount);
        $secondTenant = $this->import($path, false, $options, $otherAccount, $otherUser);
        $duplicate = $this->import($path, false, $options);

        $this->assertSame(1, $first->importedRows);
        $this->assertSame(1, $secondAccount->importedRows);
        $this->assertSame(1, $secondTenant->importedRows);
        $this->assertSame(0, $duplicate->importedRows);
        $this->assertSame(1, $duplicate->duplicateRows);
        $transactions = BankTransaction::withoutGlobalScopes()
            ->where('provider_id', 'SCOPE-1')
            ->orderBy('id')
            ->get();
        $this->assertCount(3, $transactions);
        $this->assertCount(1, $transactions->pluck('source_hash')->unique());
        $this->assertEqualsCanonicalizing(
            [$this->account->id, $reserveAccount->id, $otherAccount->id],
            $transactions->pluck('bank_account_id')->all(),
        );
        $this->assertEqualsCanonicalizing(
            [$this->business->id, $otherBusiness->id],
            $transactions->pluck('business_id')->unique()->values()->all(),
        );
    }

    public function test_import_enforces_limits_account_state_permission_and_tenant_membership(): void
    {
        config()->set('finance.bank_statement_import.max_rows', 1);

        try {
            $this->import($this->statementFile(), true);
            $this->fail('The row limit should be enforced.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('row limit', $exception->getMessage());
        }

        config()->set('finance.bank_statement_import.max_rows', 5000);
        $outsider = User::factory()->create(['is_active' => true]);
        $outsider->givePermissionTo('manage_finance');

        try {
            app(ImportBankStatementCsvAction::class)->execute($this->account, $outsider, $this->statementFile(), [], []);
            $this->fail('A user from another tenant must not import a statement.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('does not belong', $exception->getMessage());
        }

        $this->user->revokePermissionTo('manage_finance');

        try {
            $this->import($this->statementFile(), true);
            $this->fail('Finance management permission must be required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot import', $exception->getMessage());
        }

        $this->user->givePermissionTo('manage_finance');
        $this->account->forceFill(['status' => BankAccount::STATUS_ARCHIVED])->save();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('active bank account');
        $this->import($this->statementFile(), true);
    }

    public function test_authorized_bank_account_view_exposes_the_import_action(): void
    {
        $this->actingAs($this->user)
            ->get(BankAccountResource::getUrl('view', ['record' => $this->account]))
            ->assertOk()
            ->assertSee(__('finance.bank_imports.actions.import_csv'));
    }

    /** @param array<string, mixed> $overrides */
    private function import(
        string $path,
        bool $dryRun,
        array $overrides = [],
        ?BankAccount $account = null,
        ?User $user = null,
    ): BankStatementImportResult {
        return app(ImportBankStatementCsvAction::class)->execute($account ?? $this->account, $user ?? $this->user, $path, [], [
            'delimiter' => 'auto',
            'date_format' => 'auto',
            'decimal_separator' => 'comma',
            'dry_run' => $dryRun,
            'original_filename' => basename($path),
            ...$overrides,
        ]);
    }

    private function statementFile(): string
    {
        return $this->putCsv('statement.csv', "\xEF\xBB\xBFDate;Value Date;Money Out;Money In;Currency;Description;Payee;Transaction ID\n"
            ."05/11/2026;;12,34;;GBP;Hosting;Vendor;tx-1\n"
            ."06/11/2026;06/11/2026;;1000,50;GBP;Client payment;Client A;tx-2\n"
            ."31/02/2026;;;5,00;GBP;Invalid;Unknown;tx-3\n");
    }

    private function putCsv(string $name, string $contents): string
    {
        Storage::disk('local')->put($name, $contents);

        return Storage::disk('local')->path($name);
    }
}
