<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');

        $response->assertStatus(200);
    }

    public function test_new_users_can_register(): void
    {
        $this->seed(AdminSeeder::class);

        $response = $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $this->assertAuthenticated();

        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('client'));
        $this->assertDatabaseHas('clients', [
            'primary_contact_email' => 'test@example.com',
            'portal_user_id'        => $user->id,
        ]);

        $response->assertRedirect(route('portal.dashboard', absolute: false));
    }
}
