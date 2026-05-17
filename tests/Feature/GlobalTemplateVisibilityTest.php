<?php

namespace Tests\Feature;

use App\Models\BriefingTemplate;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\SalesOfferTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Global Template Visibility Test
 *
 * Verifies that BriefingTemplate and SalesOfferTemplate with business_id = NULL
 * (global templates) are visible to every tenant, while private templates
 * are isolated to their owner tenant.
 *
 * These models intentionally do NOT use BelongsToTenant — this test acts
 * as a regression guard against accidentally adding the trait.
 *
 * Run: php artisan test --filter=GlobalTemplateVisibilityTest
 */
class GlobalTemplateVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private Business $businessA;
    private Business $businessB;
    private User     $userA;
    private User     $userB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->businessA = Business::create([
            'name'      => 'Tenant Alpha',
            'slug'      => 'tenant-alpha',
            'is_active' => true,
        ]);

        $this->businessB = Business::create([
            'name'      => 'Tenant Beta',
            'slug'      => 'tenant-beta',
            'is_active' => true,
        ]);

        $this->userA = User::factory()->create(['email' => 'user.a@test.local']);
        BusinessUser::create([
            'business_id' => $this->businessA->id,
            'user_id'     => $this->userA->id,
            'role'        => 'owner',
            'is_active'   => true,
        ]);

        $this->userB = User::factory()->create(['email' => 'user.b@test.local']);
        BusinessUser::create([
            'business_id' => $this->businessB->id,
            'user_id'     => $this->userB->id,
            'role'        => 'owner',
            'is_active'   => true,
        ]);
    }

    // ── BriefingTemplate ─────────────────────────────────────────────────────

    /** @test */
    public function test_global_briefing_template_is_visible_to_tenant_a(): void
    {
        $global = BriefingTemplate::create([
            'business_id' => null,
            'title'       => 'Global Discovery',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $results = BriefingTemplate::forBusiness($this->businessA->id)->get();

        $this->assertTrue($results->contains('id', $global->id));
    }

    /** @test */
    public function test_global_briefing_template_is_visible_to_tenant_b(): void
    {
        $global = BriefingTemplate::create([
            'business_id' => null,
            'title'       => 'Global Discovery',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $results = BriefingTemplate::forBusiness($this->businessB->id)->get();

        $this->assertTrue($results->contains('id', $global->id));
    }

    /** @test */
    public function test_private_briefing_template_of_tenant_a_is_not_visible_to_tenant_b(): void
    {
        $privateA = BriefingTemplate::create([
            'business_id' => $this->businessA->id,
            'title'       => 'Alpha Private Template',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $results = BriefingTemplate::forBusiness($this->businessB->id)->get();

        $this->assertFalse($results->contains('id', $privateA->id));
    }

    /** @test */
    public function test_private_briefing_template_of_tenant_a_is_visible_to_tenant_a(): void
    {
        $privateA = BriefingTemplate::create([
            'business_id' => $this->businessA->id,
            'title'       => 'Alpha Private Template',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $results = BriefingTemplate::forBusiness($this->businessA->id)->get();

        $this->assertTrue($results->contains('id', $privateA->id));
    }

    /** @test */
    public function test_briefing_template_scope_returns_both_global_and_private_for_owner(): void
    {
        $global = BriefingTemplate::create([
            'business_id' => null,
            'title'       => 'Global',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $privateA = BriefingTemplate::create([
            'business_id' => $this->businessA->id,
            'title'       => 'Private Alpha',
            'type'        => 'discovery',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $results = BriefingTemplate::forBusiness($this->businessA->id)->get();

        $this->assertTrue($results->contains('id', $global->id));
        $this->assertTrue($results->contains('id', $privateA->id));
        $this->assertCount(2, $results);
    }

    // ── SalesOfferTemplate ────────────────────────────────────────────────────

    /** @test */
    public function test_global_sales_offer_template_is_visible_to_tenant_a(): void
    {
        $global = SalesOfferTemplate::create([
            'business_id' => null,
            'title'       => 'Global Offer',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $this->actingAs($this->userA);

        $results = SalesOfferTemplate::forBusiness()->get();

        $this->assertTrue($results->contains('id', $global->id));
    }

    /** @test */
    public function test_global_sales_offer_template_is_visible_to_tenant_b(): void
    {
        $global = SalesOfferTemplate::create([
            'business_id' => null,
            'title'       => 'Global Offer',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $this->actingAs($this->userB);

        $results = SalesOfferTemplate::forBusiness()->get();

        $this->assertTrue($results->contains('id', $global->id));
    }

    /** @test */
    public function test_private_sales_offer_template_of_tenant_a_is_not_visible_to_tenant_b(): void
    {
        $privateA = SalesOfferTemplate::create([
            'business_id' => $this->businessA->id,
            'title'       => 'Alpha Private Offer',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $this->actingAs($this->userB);

        $results = SalesOfferTemplate::forBusiness()->get();

        $this->assertFalse($results->contains('id', $privateA->id));
    }

    /** @test */
    public function test_private_sales_offer_template_of_tenant_a_is_visible_to_tenant_a(): void
    {
        $privateA = SalesOfferTemplate::create([
            'business_id' => $this->businessA->id,
            'title'       => 'Alpha Private Offer',
            'language'    => 'en',
            'is_active'   => true,
        ]);

        $this->actingAs($this->userA);

        $results = SalesOfferTemplate::forBusiness()->get();

        $this->assertTrue($results->contains('id', $privateA->id));
    }
}
