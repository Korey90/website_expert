<?php

namespace Tests\Feature\Finance;

use App\Actions\Finance\CreateUniversalCreditAssessmentPeriodAction;
use App\Actions\Finance\InitializeAccountingSetupAction;
use App\Actions\Finance\UpdateAccountingProfileAction;
use App\Actions\Finance\UpdateUniversalCreditProfileAction;
use App\Filament\Resources\RoleResource;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\UniversalCreditAssessmentPeriod;
use App\Models\UniversalCreditPolicyVersion;
use App\Models\UniversalCreditProfile;
use App\Models\User;
use App\Services\Finance\UniversalCreditPolicyHashService;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UniversalCreditFoundationTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        $this->business = Business::create([
            'name' => 'NI Sole Trader',
            'slug' => 'ni-sole-trader',
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
        $this->owner->givePermissionTo([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
            'export_universal_credit_pack',
        ]);
        $this->actingAs($this->owner);

        app(InitializeAccountingSetupAction::class)
            ->execute($this->business, $this->owner)['profile']
            ->update([
                'entity_type' => 'sole_trader',
                'vat_registered' => false,
            ]);
    }

    public function test_migration_seeds_a_hash_verified_policy_and_visible_permissions(): void
    {
        $policy = UniversalCreditPolicyVersion::query()->firstOrFail();

        $this->assertSame('ni_uc_self_employment', $policy->code);
        $this->assertSame(1, $policy->version);
        $this->assertSame(
            $policy->rules_hash,
            app(UniversalCreditPolicyHashService::class)->hash($policy->rules),
        );
        $this->assertSame(833, data_get($policy->rules, 'vehicle_mileage.car_van_or_other_motor_vehicle.first_band_miles'));
        $this->assertSame(4100, data_get($policy->rules, 'business_loan_interest.monthly_limit_pence'));

        foreach ([
            'view_universal_credit_reporting',
            'manage_universal_credit_reporting',
            'export_universal_credit_pack',
        ] as $permission) {
            $this->assertDatabaseHas('permissions', ['name' => $permission, 'guard_name' => 'web']);
            $this->assertTrue(RoleResource::permissionMatchesGroup(
                $permission,
                RoleResource::permissionGroups()['finance'],
            ));
        }
    }

    public function test_owner_can_enable_a_non_vat_profile_and_values_are_tenant_scoped(): void
    {
        $profile = app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
            'default_assessment_period_start_day' => 23,
        ]);

        $this->assertTrue($profile->is_enabled);
        $this->assertSame(23, $profile->default_assessment_period_start_day);
        $this->assertNull($profile->vat_reporting_method);
        $this->assertSame($this->owner->id, $profile->created_by);
        $this->assertSame($this->owner->id, $profile->updated_by);
        $this->assertTrue($this->business->universalCreditProfile()->is($profile));
    }

    public function test_vat_registered_profile_requires_a_method_and_effective_date(): void
    {
        $accountingProfile = $this->business->accountingProfile()->withoutGlobalScopes()->firstOrFail();
        $accountingProfile->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2026-11-01',
        ]);

        try {
            app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
                'is_enabled' => true,
            ]);
            $this->fail('A VAT method should be required.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.vat_method_required'), $exception->getMessage());
        }

        $profile = app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
            'vat_reporting_method' => UniversalCreditProfile::VAT_INCLUSIVE,
            'vat_method_effective_from' => '2026-11-01',
        ]);

        $this->assertSame(UniversalCreditProfile::VAT_INCLUSIVE, $profile->vat_reporting_method);
        $this->assertSame('2026-11-01', $profile->vat_method_effective_from?->toDateString());
    }

    public function test_non_owner_cannot_change_the_profile_even_with_manage_permission(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $member->givePermissionTo('manage_universal_credit_reporting');

        $this->expectException(DomainException::class);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $member, [
            'is_enabled' => true,
        ]);
    }

    public function test_it_creates_an_immutable_zero_value_period_with_policy_snapshot(): void
    {
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);

        $period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );

        $this->assertSame('UC-20261123-20261222-V1', $period->number);
        $this->assertSame(UniversalCreditAssessmentPeriod::STATUS_DRAFT, $period->status);
        $this->assertSame(0, $period->income_total_pence);
        $this->assertSame(0, $period->expense_total_pence);
        $this->assertSame(0, $period->net_total_pence);
        $this->assertNull($period->vat_reporting_method_snapshot);
        $this->assertSame(
            $period->policy_rules_hash,
            app(UniversalCreditPolicyHashService::class)->hash($period->policy_rules_snapshot),
        );

        try {
            $period->update(['date_to' => '2026-12-21']);
            $this->fail('Foundation fields should be immutable.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.universal_credit.errors.period_foundation_immutable'), $exception->getMessage());
        }
    }

    public function test_it_snapshots_the_vat_method_effective_for_the_vat_registered_part_of_period(): void
    {
        $this->business->accountingProfile()->withoutGlobalScopes()->update([
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
            'vat_scheme' => 'standard',
            'vat_effective_from' => '2026-12-01',
        ]);
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
            'vat_reporting_method' => UniversalCreditProfile::VAT_EXCLUSIVE,
            'vat_method_effective_from' => '2026-12-01',
        ]);

        $period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );

        $this->assertSame(UniversalCreditProfile::VAT_EXCLUSIVE, $period->vat_reporting_method_snapshot);
    }

    public function test_it_rejects_overlaps_long_ranges_and_periods_without_policy_coverage(): void
    {
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        $action = app(CreateUniversalCreditAssessmentPeriodAction::class);
        $action->execute($this->business, $this->owner, '2026-11-23', '2026-12-22');

        foreach ([
            ['2026-12-01', '2026-12-30', 'period_overlap'],
            ['2027-01-01', '2027-02-01', 'period_too_long'],
            ['2026-10-23', '2026-11-21', 'policy_not_found'],
        ] as [$from, $to, $error]) {
            try {
                $action->execute($this->business, $this->owner, $from, $to);
                $this->fail("The {$error} validation should reject the period.");
            } catch (DomainException $exception) {
                $this->assertSame(__("finance.universal_credit.errors.{$error}"), $exception->getMessage());
            }
        }
    }

    public function test_periods_are_isolated_per_tenant_and_policies_check_current_business(): void
    {
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        $period = app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );

        $otherBusiness = Business::create(['name' => 'Other Trader', 'slug' => 'other-trader', 'is_active' => true]);
        $otherUser = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $otherBusiness->id,
            'user_id' => $otherUser->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        $otherUser->givePermissionTo(['view_universal_credit_reporting', 'manage_universal_credit_reporting']);

        $this->actingAs($otherUser);
        $this->assertFalse(Gate::forUser($otherUser)->allows('view', $period));
        $this->assertSame(0, UniversalCreditAssessmentPeriod::query()->count());
        $this->assertSame(1, UniversalCreditAssessmentPeriod::withoutGlobalScopes()->count());
    }

    public function test_entity_type_cannot_change_when_uc_is_enabled(): void
    {
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        Permission::findOrCreate('manage_finance', 'web');
        $this->owner->givePermissionTo('manage_finance');
        $accountingProfile = $this->business->accountingProfile()->withoutGlobalScopes()->firstOrFail();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.universal_credit.errors.entity_type_change_blocked'));
        app(UpdateAccountingProfileAction::class)->execute($accountingProfile, $this->owner, [
            'entity_type' => 'limited_company',
        ]);
    }

    public function test_tenant_audit_accepts_valid_uc_foundations(): void
    {
        app(UpdateUniversalCreditProfileAction::class)->execute($this->business, $this->owner, [
            'is_enabled' => true,
        ]);
        app(CreateUniversalCreditAssessmentPeriodAction::class)->execute(
            $this->business,
            $this->owner,
            '2026-11-23',
            '2026-12-22',
        );

        $this->artisan('finance:audit-tenants')->assertSuccessful();
    }
}
