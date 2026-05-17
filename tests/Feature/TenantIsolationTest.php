<?php

namespace Tests\Feature;

use App\Models\ApiToken;
use App\Models\Briefing;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\SalesOffer;
use App\Models\User;
use App\Scopes\BusinessScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tenant Isolation Test
 *
 * Verifies that BelongsToTenant GlobalScope correctly isolates data
 * between tenants for: Lead, Client, Briefing, SalesOffer, ApiToken.
 *
 * Run: php artisan test --filter=TenantIsolationTest
 */
class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    private Business      $businessA;
    private Business      $businessB;
    private User          $userA;
    private User          $userB;
    private PipelineStage $stage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);

        $this->stage = PipelineStage::firstOrCreate(
            ['slug' => 'new-lead'],
            ['name' => 'New Lead', 'color' => '#6B7280', 'order' => 1, 'is_won' => false, 'is_lost' => false],
        );

        // Business A
        $this->businessA = Business::create([
            'name'      => 'Agency Alpha',
            'slug'      => 'agency-alpha',
            'is_active' => true,
        ]);

        // Business B
        $this->businessB = Business::create([
            'name'      => 'Agency Beta',
            'slug'      => 'agency-beta',
            'is_active' => true,
        ]);

        // User A — member of Business A
        $this->userA = User::factory()->create(['email' => 'user.a@test.local']);
        BusinessUser::create([
            'business_id' => $this->businessA->id,
            'user_id'     => $this->userA->id,
            'role'        => 'owner',
            'is_active'   => true,
        ]);

        // User B — member of Business B
        $this->userB = User::factory()->create(['email' => 'user.b@test.local']);
        BusinessUser::create([
            'business_id' => $this->businessB->id,
            'user_id'     => $this->userB->id,
            'role'        => 'owner',
            'is_active'   => true,
        ]);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function createRawLead(Business $business, string $title = 'Test Lead'): Lead
    {
        return Lead::withoutGlobalScope(BusinessScope::class)->create([
            'title'              => $title,
            'source'             => 'manual',
            'business_id'        => $business->id,
            'pipeline_stage_id'  => $this->stage->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // Lead isolation
    // -------------------------------------------------------------------------

    public function test_lead_is_auto_filled_with_business_id_on_creation(): void
    {
        $this->actingAs($this->userA);

        $lead = Lead::create([
            'title'             => 'Auto-fill Lead',
            'source'            => 'manual',
            'pipeline_stage_id' => $this->stage->id,
        ]);

        $this->assertSame($this->businessA->id, $lead->business_id);
    }

    public function test_user_only_sees_leads_from_their_own_tenant(): void
    {
        $this->createRawLead($this->businessA, 'Lead A');

        // Authenticated as User B — should see 0 leads (none belong to Business B)
        $this->actingAs($this->userB);

        $this->assertSame(0, Lead::count());
    }

    public function test_user_sees_only_their_own_tenants_leads(): void
    {
        $this->createRawLead($this->businessA, 'Lead A');
        $this->createRawLead($this->businessB, 'Lead B');

        // User A sees only their 1 lead
        $this->actingAs($this->userA);
        $this->assertSame(1, Lead::count());
        $this->assertSame('Lead A', Lead::first()->title);

        // User B sees only their 1 lead
        $this->actingAs($this->userB);
        $this->assertSame(1, Lead::count());
        $this->assertSame('Lead B', Lead::first()->title);
    }

    // -------------------------------------------------------------------------
    // Client isolation
    // -------------------------------------------------------------------------

    public function test_client_is_auto_filled_with_business_id_on_creation(): void
    {
        $this->actingAs($this->userA);

        $client = Client::create([
            'company_name' => 'Auto Corp',
            'status'       => 'prospect',
        ]);

        $this->assertSame($this->businessA->id, $client->business_id);
    }

    public function test_user_only_sees_clients_from_their_own_tenant(): void
    {
        Client::withoutGlobalScope(BusinessScope::class)->create([
            'company_name' => 'Client A',
            'status'       => 'prospect',
            'business_id'  => $this->businessA->id,
        ]);

        $this->actingAs($this->userB);

        $this->assertSame(0, Client::count());
    }

    // -------------------------------------------------------------------------
    // Briefing isolation
    // -------------------------------------------------------------------------

    public function test_briefing_is_auto_filled_with_business_id_on_creation(): void
    {
        $leadA = $this->createRawLead($this->businessA);

        $this->actingAs($this->userA);

        $briefing = Briefing::create([
            'title'   => 'Discovery Call',
            'type'    => 'discovery',
            'status'  => 'draft',
            'lead_id' => $leadA->id,
        ]);

        $this->assertSame($this->businessA->id, $briefing->business_id);
    }

    public function test_user_only_sees_briefings_from_their_own_tenant(): void
    {
        $leadA = $this->createRawLead($this->businessA);

        Briefing::withoutGlobalScope(BusinessScope::class)->create([
            'title'       => 'Briefing A',
            'type'        => 'discovery',
            'status'      => 'draft',
            'lead_id'     => $leadA->id,
            'business_id' => $this->businessA->id,
        ]);

        $this->actingAs($this->userB);

        $this->assertSame(0, Briefing::count());
    }

    // -------------------------------------------------------------------------
    // SalesOffer isolation
    // -------------------------------------------------------------------------

    public function test_sales_offer_is_auto_filled_with_business_id_on_creation(): void
    {
        $leadA = $this->createRawLead($this->businessA);

        $this->actingAs($this->userA);

        $offer = SalesOffer::create([
            'title'    => 'Offer A',
            'language' => 'en',
            'status'   => 'draft',
            'body'     => '<p>Test</p>',
            'lead_id'  => $leadA->id,
        ]);

        $this->assertSame($this->businessA->id, $offer->business_id);
    }

    public function test_user_only_sees_sales_offers_from_their_own_tenant(): void
    {
        $leadA = $this->createRawLead($this->businessA);

        SalesOffer::withoutGlobalScope(BusinessScope::class)->create([
            'title'       => 'Offer A',
            'language'    => 'en',
            'status'      => 'draft',
            'body'        => '<p>Test</p>',
            'lead_id'     => $leadA->id,
            'business_id' => $this->businessA->id,
        ]);

        $this->actingAs($this->userB);

        $this->assertSame(0, SalesOffer::count());
    }

    // -------------------------------------------------------------------------
    // ApiToken isolation
    // -------------------------------------------------------------------------

    public function test_api_token_is_auto_filled_with_business_id_on_creation(): void
    {
        $this->actingAs($this->userA);

        $token = ApiToken::create([
            'name'       => 'CI Token',
            'token_hash' => hash('sha256', 'test-token-value'),
            'is_active'  => true,
            'created_by' => $this->userA->id,
        ]);

        $this->assertSame($this->businessA->id, $token->business_id);
    }

    public function test_user_only_sees_api_tokens_from_their_own_tenant(): void
    {
        ApiToken::withoutGlobalScope(BusinessScope::class)->create([
            'name'        => 'Token A',
            'token_hash'  => hash('sha256', 'secret-a'),
            'is_active'   => true,
            'business_id' => $this->businessA->id,
            'created_by'  => $this->userA->id,
        ]);

        $this->actingAs($this->userB);

        $this->assertSame(0, ApiToken::count());
    }

    // -------------------------------------------------------------------------
    // Public context (no auth) — GlobalScope must be a no-op
    // -------------------------------------------------------------------------

    public function test_global_scope_is_skipped_when_no_user_is_authenticated(): void
    {
        $this->createRawLead($this->businessA, 'Public Lead');

        // Ensure no user is logged in
        auth()->guard()->logout();
        $this->assertNull(auth()->user());

        // Without an active business the scope is a no-op — all records visible
        $this->assertSame(1, Lead::count());
    }

    // -------------------------------------------------------------------------
    // Cross-tenant data access must be denied
    // -------------------------------------------------------------------------

    public function test_cross_tenant_lead_is_not_accessible_via_find(): void
    {
        $lead = $this->createRawLead($this->businessA, 'Secret Lead');

        $this->actingAs($this->userB);

        $this->assertNull(Lead::find($lead->id));
    }
}
