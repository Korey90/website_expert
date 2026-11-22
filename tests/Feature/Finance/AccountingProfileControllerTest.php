<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'Example Agency Ltd',
            'slug' => 'example-agency',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        Permission::findOrCreate('manage_finance', 'web');
        $this->owner->givePermissionTo('manage_finance');
    }

    public function test_owner_can_open_the_profile_without_exposing_sensitive_values(): void
    {
        $profile = app(InitializeAccountingSetupAction::class)->execute($this->business, $this->owner)['profile'];
        $profile->update([
            'company_utr' => '1234567890',
            'paye_reference' => '123/AB456',
            'accounts_office_reference' => '123PA00045678',
        ]);

        $this->actingAs($this->owner)
            ->get(route('business.accounting.edit'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Business/AccountingProfile')
                ->where('profile.has_company_utr', true)
                ->where('profile.has_paye_reference', true)
                ->where('profile.has_accounts_office_reference', true)
                ->missing('profile.company_utr')
                ->missing('profile.paye_reference')
                ->missing('profile.accounts_office_reference'));
    }

    public function test_owner_can_update_a_valid_uk_accounting_profile(): void
    {
        $this->actingAs($this->owner)
            ->patch(route('business.accounting.update'), $this->validPayload())
            ->assertRedirect()
            ->assertSessionHas('success');

        $profile = $this->business->accountingProfile()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('Example Agency Limited', $profile->legal_name);
        $this->assertSame('12345678', $profile->company_number);
        $this->assertSame('1234567890', $profile->company_utr);
        $this->assertSame('GB123456789', $profile->vat_number);
        $this->assertSame($this->owner->id, $profile->updated_by);
    }

    public function test_vat_details_are_required_for_a_registered_business(): void
    {
        $payload = $this->validPayload();
        $payload['vat_number'] = '';
        $payload['vat_scheme'] = '';
        $payload['vat_effective_from'] = '';

        $this->actingAs($this->owner)
            ->from(route('business.accounting.edit'))
            ->patch(route('business.accounting.update'), $payload)
            ->assertRedirect(route('business.accounting.edit'))
            ->assertSessionHasErrors(['vat_number', 'vat_scheme', 'vat_effective_from']);
    }

    public function test_company_number_is_required_for_a_limited_company(): void
    {
        $payload = $this->validPayload();
        $payload['company_number'] = '';

        $this->actingAs($this->owner)
            ->patch(route('business.accounting.update'), $payload)
            ->assertSessionHasErrors('company_number');
    }

    public function test_regular_member_cannot_manage_accounting(): void
    {
        $member = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('business.accounting.edit'))
            ->assertForbidden();
    }

    public function test_inactive_admin_is_redirected_to_onboarding(): void
    {
        $admin = User::factory()->create();
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $admin->id,
            'role' => 'admin',
            'is_active' => false,
            'joined_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('business.accounting.edit'))
            ->assertRedirect('/onboarding');
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'entity_type' => 'limited_company',
            'legal_name' => 'Example Agency Limited',
            'company_number' => '12345678',
            'registered_address' => [
                'line_1' => '10 Downing Street',
                'line_2' => '',
                'city' => 'London',
                'county' => '',
                'postcode' => 'SW1A 2AA',
                'country' => 'GB',
            ],
            'company_utr' => '12345 67890',
            'functional_currency' => 'GBP',
            'accounting_basis' => 'accrual',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'vat_registered' => true,
            'vat_number' => 'GB 123 456 789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2026-04-01',
            'vat_effective_to' => null,
            'paye_reference' => '123/AB456',
            'accounts_office_reference' => '123PA00045678',
        ];
    }
}
