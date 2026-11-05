<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateBankAccountAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateBankAccountAction;
use App\Filament\Resources\BankAccountResource;
use App\Models\BankAccount;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\LedgerAccount;
use App\Models\User;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class BankAccountTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $user;

    private LedgerAccount $cashAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create(['name' => 'Banking Ltd', 'slug' => 'banking', 'is_active' => true]);
        $this->user = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        app(InitializeAccountingSetupAction::class)->execute($this->business, $this->user);
        $this->cashAccount = $this->account($this->business, '1100');

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }
        $this->user->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);
    }

    public function test_it_never_persists_full_bank_credentials_and_exposes_only_masks(): void
    {
        $account = $this->createAccount([
            'account_number' => '1234 5678',
            'sort_code' => '12-34-56',
            'opening_balance' => '1250.45',
            'opening_balance_date' => '2026-11-01',
        ]);
        $row = (array) DB::table('bank_accounts')->where('id', $account->id)->first();
        $serialized = json_encode($row, JSON_THROW_ON_ERROR);

        $this->assertFalse(Schema::hasColumn('bank_accounts', 'account_number'));
        $this->assertFalse(Schema::hasColumn('bank_accounts', 'sort_code'));
        $this->assertSame('5678', $row['account_number_last4']);
        $this->assertSame('56', $row['sort_code_last2']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $row['account_fingerprint']);
        $this->assertStringNotContainsString('12345678', $serialized);
        $this->assertStringNotContainsString('123456', $serialized);
        $this->assertSame('••••5678', $account->masked_account_number);
        $this->assertSame('••-••-56', $account->masked_sort_code);
        $this->assertSame('1250.45', $account->opening_balance);
        $this->assertSame('2026-11-01', $account->opening_balance_date->toDateString());
        $this->assertArrayNotHasKey('account_fingerprint', $account->toArray());
        $this->assertTrue($account->ledgerAccount->is($this->cashAccount));
        $this->assertTrue($account->business->is($this->business));
    }

    public function test_invalid_or_duplicate_bank_details_are_rejected(): void
    {
        $this->createAccount();

        try {
            $this->createAccount();
            $this->fail('A duplicate bank account should be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('already registered', $exception->getMessage());
        }

        foreach ([
            ['account_number' => '1234567', 'sort_code' => '123456', 'message' => '8 digits'],
            ['account_number' => '12345678', 'sort_code' => '12345', 'message' => '6 digits'],
            ['account_number' => '12345678', 'sort_code' => '', 'message' => 'supplied together'],
        ] as $invalid) {
            try {
                $this->createAccount([
                    'account_number' => $invalid['account_number'],
                    'sort_code' => $invalid['sort_code'],
                    'name' => fake()->unique()->word(),
                ]);
                $this->fail('Invalid protected bank details should be rejected.');
            } catch (DomainException $exception) {
                $this->assertStringContainsString($invalid['message'], $exception->getMessage());
            }
        }

        $this->assertDatabaseCount('bank_accounts', 1);
    }

    public function test_update_preserves_or_replaces_protected_details_and_audits_archiving(): void
    {
        $account = $this->createAccount();
        $fingerprint = $account->getRawOriginal('account_fingerprint');

        $archived = app(UpdateBankAccountAction::class)->execute($account, $this->user, [
            'name' => 'Archived operating account',
            'bank_name' => 'Example Bank',
            'ledger_account_id' => $this->cashAccount->id,
            'currency' => 'GBP',
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ARCHIVED,
        ]);

        $this->assertSame($fingerprint, $archived->getRawOriginal('account_fingerprint'));
        $this->assertSame('5678', $archived->account_number_last4);
        $this->assertSame($this->user->id, $archived->archived_by);
        $this->assertNotNull($archived->archived_at);

        $active = app(UpdateBankAccountAction::class)->execute($archived, $this->user, [
            'name' => 'Replacement operating account',
            'bank_name' => 'Example Bank',
            'ledger_account_id' => $this->cashAccount->id,
            'currency' => 'EUR',
            'opening_balance' => -25.50,
            'status' => BankAccount::STATUS_ACTIVE,
            'account_number' => '87654321',
            'sort_code' => '65-43-21',
        ]);

        $this->assertNotSame($fingerprint, $active->getRawOriginal('account_fingerprint'));
        $this->assertSame('4321', $active->account_number_last4);
        $this->assertSame('21', $active->sort_code_last2);
        $this->assertSame('EUR', $active->currency);
        $this->assertSame('-25.50', $active->opening_balance);
        $this->assertNull($active->archived_by);
        $this->assertNull($active->archived_at);
    }

    public function test_actions_policy_and_queries_enforce_tenant_and_permission_boundaries(): void
    {
        $account = $this->createAccount();
        $otherBusiness = Business::create(['name' => 'Other Bank Ltd', 'slug' => 'other-bank', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
        ]);
        app(InitializeAccountingSetupAction::class)->execute($otherBusiness, $otherUser);
        $foreignCash = $this->account($otherBusiness, '1100');

        try {
            $this->createAccount(['ledger_account_id' => $foreignCash->id, 'account_number' => '87654321']);
            $this->fail('A foreign ledger account must be rejected.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('same business', $exception->getMessage());
        }

        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create(['business_id' => $this->business->id, 'user_id' => $member->id, 'role' => 'member', 'is_active' => true]);

        try {
            app(CreateBankAccountAction::class)->execute($this->business, $member, $this->payload(['account_number' => '87654321']));
            $this->fail('Finance management permission must be required.');
        } catch (DomainException $exception) {
            $this->assertStringContainsString('cannot manage', $exception->getMessage());
        }

        $this->actingAs($this->user);
        $this->assertTrue(Gate::forUser($this->user)->allows('view', $account));
        $this->assertTrue(Gate::forUser($this->user)->allows('update', $account));
        $this->assertFalse(Gate::forUser($this->user)->allows('delete', $account));
        $this->assertSame(1, BankAccount::count());
    }

    public function test_authorized_resource_pages_show_masks_and_never_raw_credentials(): void
    {
        $account = $this->createAccount();

        $this->actingAs($this->user)
            ->get(BankAccountResource::getUrl('index'))
            ->assertOk()
            ->assertSee('5678')
            ->assertDontSee('12345678');
        $this->get(BankAccountResource::getUrl('create'))->assertOk();
        $this->get(BankAccountResource::getUrl('view', ['record' => $account]))
            ->assertOk()
            ->assertSee('5678')
            ->assertSee('56')
            ->assertDontSee('12345678')
            ->assertDontSee('123456');
        $this->get(BankAccountResource::getUrl('edit', ['record' => $account]))
            ->assertOk()
            ->assertDontSee('12345678')
            ->assertDontSee('123456');
    }

    public function test_bank_accounts_are_archived_instead_of_deleted(): void
    {
        $account = $this->createAccount();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('archive it instead');
        $account->delete();
    }

    /** @param array<string, mixed> $overrides */
    private function createAccount(array $overrides = []): BankAccount
    {
        return app(CreateBankAccountAction::class)->execute(
            $this->business,
            $this->user,
            $this->payload($overrides),
        );
    }

    /** @param array<string, mixed> $overrides */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Operating account',
            'bank_name' => 'Example Bank',
            'account_number' => '12345678',
            'sort_code' => '12-34-56',
            'currency' => 'GBP',
            'ledger_account_id' => $this->cashAccount->id,
            'opening_balance' => 0,
            'status' => BankAccount::STATUS_ACTIVE,
            ...$overrides,
        ];
    }

    private function account(Business $business, string $code): LedgerAccount
    {
        return LedgerAccount::withoutGlobalScopes()
            ->where('business_id', $business->id)
            ->where('code', $code)
            ->firstOrFail();
    }
}
