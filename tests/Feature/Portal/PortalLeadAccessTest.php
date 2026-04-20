<?php

namespace Tests\Feature\Portal;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalLeadAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_portal_only_client_is_redirected_away_from_workspace_lead_page(): void
    {
        $user = User::factory()->create();

        Client::create([
            'company_name'          => 'Agency Client Ltd',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        $stage = PipelineStage::create([
            'name'  => 'New',
            'slug'  => 'new',
            'order' => 1,
        ]);

        $lead = Lead::create([
            'title'             => 'Workspace Lead',
            'pipeline_stage_id' => $stage->id,
            'source'            => 'landing_page',
        ]);

        $this->actingAs($user)
            ->get(route('portal.leads.show', $lead))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error', 'Workspace access is required to view captured leads.');
    }

    public function test_workspace_member_can_open_portal_lead_page_without_client_binding(): void
    {
        $user = User::factory()->create();

        $business = Business::create([
            'name'      => 'Workspace Ltd',
            'slug'      => 'workspace-ltd',
            'locale'    => 'en',
            'timezone'  => 'Europe/London',
            'plan'      => 'free',
            'is_active' => true,
        ]);

        BusinessUser::create([
            'business_id' => $business->id,
            'user_id'     => $user->id,
            'role'        => 'owner',
            'is_active'   => true,
            'joined_at'   => now(),
        ]);

        $stage = PipelineStage::create([
            'name'  => 'New',
            'slug'  => 'new',
            'order' => 1,
        ]);

        $lead = Lead::create([
            'title'             => 'Workspace Lead',
            'pipeline_stage_id' => $stage->id,
            'source'            => 'landing_page',
            'business_id'       => $business->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.leads.show', $lead))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Leads/Show')
                ->where('lead.id', $lead->id)
                ->where('client', null)
            );
    }
}