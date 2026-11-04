<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateAccountingProfileAction;
use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpdateAccountingProfileActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_updates_the_profile_and_normalizes_inapplicable_fields(): void
    {
        $business = Business::create(['name' => 'UK Books Ltd', 'slug' => 'uk-books', 'is_active' => true]);
        $actor = User::factory()->create();
        $profile = app(InitializeAccountingSetupAction::class)->execute($business, $actor)['profile'];

        $updated = app(UpdateAccountingProfileAction::class)->execute($profile, $actor, [
            'entity_type' => 'sole_trader',
            'legal_name' => 'Alex Example',
            'company_number' => 'SHOULD-BE-CLEARED',
            'registered_address' => ['line_1' => '1 High Street', 'city' => 'London', 'postcode' => 'SW1A 1AA', 'country' => 'GB'],
            'company_utr' => '1234567890',
            'functional_currency' => 'GBP',
            'accounting_basis' => 'cash',
            'financial_year_start' => '2026-04-06',
            'financial_year_end' => '2027-04-05',
            'vat_registered' => false,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2026-04-06',
            'vat_effective_to' => null,
            'paye_reference' => '123/AB456',
            'accounts_office_reference' => '123PA00045678',
        ]);

        $this->assertSame('Alex Example', $updated->legal_name);
        $this->assertNull($updated->company_number);
        $this->assertNull($updated->vat_number);
        $this->assertNull($updated->vat_scheme);
        $this->assertSame('1234567890', $updated->company_utr);
        $this->assertSame($actor->id, $updated->updated_by);
    }

    public function test_blank_sensitive_fields_preserve_existing_encrypted_values(): void
    {
        $business = Business::create(['name' => 'UK Books Ltd', 'slug' => 'uk-books', 'is_active' => true]);
        $actor = User::factory()->create();
        $profile = app(InitializeAccountingSetupAction::class)->execute($business, $actor)['profile'];
        $profile->update([
            'company_utr' => '1234567890',
            'paye_reference' => '123/AB456',
            'accounts_office_reference' => '123PA00045678',
        ]);

        $updated = app(UpdateAccountingProfileAction::class)->execute($profile, $actor, [
            'legal_name' => 'Renamed Ltd',
            'company_utr' => null,
            'paye_reference' => '',
            'accounts_office_reference' => null,
        ]);

        $this->assertSame('1234567890', $updated->company_utr);
        $this->assertSame('123/AB456', $updated->paye_reference);
        $this->assertSame('123PA00045678', $updated->accounts_office_reference);
    }
}
