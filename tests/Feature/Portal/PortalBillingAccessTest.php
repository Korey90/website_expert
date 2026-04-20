<?php

namespace Tests\Feature\Portal;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalBillingAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_portal_only_client_is_redirected_away_from_billing(): void
    {
        $user = User::factory()->create();

        Client::create([
            'company_name'          => 'Agency Client Ltd',
            'primary_contact_email' => $user->email,
            'portal_user_id'        => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.billing'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error', 'Workspace access is required for billing and plan management.');
    }

    public function test_workspace_member_can_open_billing_page(): void
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

        $this->actingAs($user)
            ->get(route('portal.billing'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Billing/Index')
                ->where('business.name', 'Workspace Ltd')
            );
    }
}