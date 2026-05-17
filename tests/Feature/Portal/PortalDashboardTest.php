<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\ClientPortalAccess;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_portal_user_sees_dashboard(): void
    {
        $user   = User::factory()->create();
        $client = Client::create([
            'company_name'          => 'Test Client Ltd',
            'primary_contact_email' => $user->email,
        ]);

        ClientPortalAccess::create(['client_id' => $client->id, 'user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Dashboard')
                ->has('client')
                ->has('projects')
                ->has('invoices')
                ->has('quotes')
            );
    }

    public function test_user_without_client_profile_sees_pending_screen(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('portal.dashboard'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Dashboard')
                ->where('client', null)
            );
    }

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $this->get(route('portal.dashboard'))
            ->assertRedirect(route('login'));
    }
}
