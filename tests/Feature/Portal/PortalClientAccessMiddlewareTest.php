<?php

namespace Tests\Feature\Portal;

use App\Models\Client;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortalClientAccessMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_user_without_client_portal_access_is_redirected_from_client_portal_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('portal.projects'))
            ->assertRedirect(route('portal.dashboard'))
            ->assertSessionHas('error', 'Client portal access is required to continue.');
    }

    public function test_portal_client_can_open_projects_index(): void
    {
        $user = User::factory()->create();

        Client::create([
            'company_name' => 'Agency Client Ltd',
            'primary_contact_email' => $user->email,
            'portal_user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->get(route('portal.projects'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Portal/Projects')
                ->where('client.company_name', 'Agency Client Ltd')
            );
    }
}