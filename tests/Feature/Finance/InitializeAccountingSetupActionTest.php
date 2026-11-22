<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\LedgerAccount;
use App\Models\TaxCode;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class InitializeAccountingSetupActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_initializes_an_idempotent_uk_accounting_setup(): void
    {
        $business = Business::create([
            'name' => 'Example Agency Ltd',
            'slug' => 'example-agency',
            'is_active' => true,
        ]);
        $actor = User::factory()->create();
        $action = app(InitializeAccountingSetupAction::class);

        $first = $action->execute($business, $actor);
        $second = $action->execute($business, $actor);

        $this->assertSame($first['profile']->id, $second['profile']->id);
        $this->assertCount(6, $first['tax_codes']);
        $this->assertCount(36, $first['ledger_accounts']);
        $this->assertSame(1, AccountingProfile::withoutGlobalScopes()->where('business_id', $business->id)->count());
        $this->assertSame(6, TaxCode::withoutGlobalScopes()->where('business_id', $business->id)->count());
        $this->assertSame(36, LedgerAccount::withoutGlobalScopes()->where('business_id', $business->id)->count());
        $this->assertSame('owner_capital', $first['ledger_accounts']->firstWhere('code', '3300')?->subtype);
        $this->assertSame('GBP', $first['profile']->functional_currency);
        $this->assertSame('20.00', $first['tax_codes']->firstWhere('code', 'S20')?->rate);
        $this->assertSame('Stawka standardowa 20%', $first['tax_codes']->firstWhere('code', 'S20')?->getTranslation('label', 'pl'));
    }

    public function test_sensitive_accounting_identifiers_are_encrypted_at_rest(): void
    {
        $business = Business::create([
            'name' => 'Secure Books Ltd',
            'slug' => 'secure-books',
            'is_active' => true,
        ]);

        $profile = app(InitializeAccountingSetupAction::class)->execute($business)['profile'];
        $profile->update([
            'company_utr' => '1234567890',
            'paye_reference' => '123/AB456',
            'accounts_office_reference' => '123PA00045678',
        ]);

        $raw = DB::table('accounting_profiles')->where('id', $profile->id)->first();

        $this->assertNotSame('1234567890', $raw->company_utr);
        $this->assertNotSame('123/AB456', $raw->paye_reference);
        $this->assertNotSame('123PA00045678', $raw->accounts_office_reference);
        $this->assertSame('1234567890', $profile->fresh()->company_utr);
        $this->assertSame('123/AB456', $profile->fresh()->paye_reference);
    }

    public function test_accounting_setup_is_isolated_between_tenants(): void
    {
        $businessA = Business::create(['name' => 'Agency A', 'slug' => 'agency-a', 'is_active' => true]);
        $businessB = Business::create(['name' => 'Agency B', 'slug' => 'agency-b', 'is_active' => true]);
        $userA = User::factory()->create();

        BusinessUser::create([
            'business_id' => $businessA->id,
            'user_id' => $userA->id,
            'role' => 'owner',
            'is_active' => true,
        ]);

        $action = app(InitializeAccountingSetupAction::class);
        $action->execute($businessA, $userA);
        $action->execute($businessB);

        $this->actingAs($userA);

        $this->assertSame(1, AccountingProfile::count());
        $this->assertSame(6, TaxCode::count());
        $this->assertSame(36, LedgerAccount::count());
        $this->assertTrue(AccountingProfile::first()->is($businessA->accountingProfile));
        $this->assertSame(0, TaxCode::where('business_id', $businessB->id)->count());
        $this->assertSame(0, LedgerAccount::where('business_id', $businessB->id)->count());
    }
}
