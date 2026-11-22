<?php

namespace Tests\Feature\Finance;

use App\Filament\Pages\AccountingProfilePage;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class AccountingProfilePageTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['access_admin_panel', 'view_finance', 'manage_finance'] as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $this->business = Business::create([
            'name' => 'Sole Trader Studio',
            'slug' => 'sole-trader-studio',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    public function test_finance_owner_can_open_and_save_the_filament_accounting_profile(): void
    {
        $this->owner->givePermissionTo(['access_admin_panel', 'view_finance', 'manage_finance']);

        Livewire::actingAs($this->owner)
            ->test(AccountingProfilePage::class)
            ->fillForm($this->validPayload())
            ->call('save')
            ->assertHasNoFormErrors()
            ->assertSet('data.company_utr', null)
            ->assertSet('hasCompanyUtr', true);

        $profile = $this->business->accountingProfile()->withoutGlobalScopes()->firstOrFail();

        $this->assertSame('sole_trader', $profile->entity_type);
        $this->assertSame('Sole Trader Studio', $profile->legal_name);
        $this->assertSame('1234567890', $profile->company_utr);
        $this->assertSame($this->owner->id, $profile->updated_by);
    }

    public function test_finance_viewer_can_open_but_cannot_manage_the_profile(): void
    {
        $viewer = User::factory()->create(['is_active' => true]);
        $viewer->givePermissionTo(['access_admin_panel', 'view_finance']);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $viewer->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($viewer);

        $this->assertTrue(AccountingProfilePage::canAccess());
        Livewire::test(AccountingProfilePage::class)
            ->assertSet('data.legal_name', 'Sole Trader Studio')
            ->assertSee(__('finance.accounting.read_only'));
    }

    public function test_user_without_view_finance_cannot_open_the_page(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $user->givePermissionTo('access_admin_panel');
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($user);

        $this->assertFalse(AccountingProfilePage::canAccess());
        $this->get(AccountingProfilePage::getUrl())->assertForbidden();
    }

    /** @return array<string, mixed> */
    private function validPayload(): array
    {
        return [
            'entity_type' => 'sole_trader',
            'legal_name' => 'Sole Trader Studio',
            'company_number' => null,
            'company_utr' => '12345 67890',
            'registered_address' => [
                'line_1' => '1 High Street',
                'line_2' => null,
                'city' => 'Belfast',
                'county' => 'County Antrim',
                'postcode' => 'BT1 1AA',
                'country' => 'GB',
            ],
            'functional_currency' => 'GBP',
            'accounting_basis' => 'cash',
            'financial_year_start' => '2026-04-06',
            'financial_year_end' => '2027-04-05',
            'vat_registered' => false,
            'vat_number' => null,
            'vat_scheme' => null,
            'vat_effective_from' => null,
            'vat_effective_to' => null,
            'paye_reference' => null,
            'accounts_office_reference' => null,
        ];
    }
}
