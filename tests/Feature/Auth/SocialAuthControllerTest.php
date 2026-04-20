<?php

namespace Tests\Feature\Auth;

use App\Models\Client;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Socialite\Contracts\User as SocialUserContract;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class SocialAuthControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();
    }

    public function test_social_login_does_not_create_account_for_unknown_user(): void
    {
        $this->mockSocialiteUser(
            $this->makeSocialUser('google-user-1', 'new-social@example.com', 'New Social User')
        );

        $response = $this
            ->withSession(['social_auth_intent' => 'login'])
            ->get(route('social.callback', ['provider' => 'google']));

        $response
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors([
                'email' => __('auth.social_account_not_registered'),
            ]);

        $this->assertDatabaseMissing('users', ['email' => 'new-social@example.com']);
        $this->assertDatabaseCount('social_accounts', 0);
    }

    public function test_social_register_creates_account_for_unknown_user(): void
    {
        $this->mockSocialiteUser(
            $this->makeSocialUser('google-user-2', 'register-social@example.com', 'Register Social User')
        );

        $response = $this
            ->withSession(['social_auth_intent' => 'register'])
            ->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('portal.dashboard', absolute: false));

        $user = User::where('email', 'register-social@example.com')->first();

        $this->assertNotNull($user);
        $this->assertTrue($user->hasRole('client'));
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-2',
        ]);
        $this->assertDatabaseHas('clients', [
            'portal_user_id' => $user->id,
            'primary_contact_email' => 'register-social@example.com',
        ]);
    }

    public function test_social_login_links_existing_registered_user_without_creating_duplicate_account(): void
    {
        $user = User::factory()->create([
            'email' => 'known-social@example.com',
            'is_active' => true,
        ]);

        $this->mockSocialiteUser(
            $this->makeSocialUser('google-user-3', 'known-social@example.com', 'Known Social User')
        );

        $response = $this
            ->withSession(['social_auth_intent' => 'login'])
            ->get(route('social.callback', ['provider' => 'google']));

        $response->assertRedirect(route('portal.dashboard', absolute: false));

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseHas('social_accounts', [
            'user_id' => $user->id,
            'provider' => 'google',
            'provider_user_id' => 'google-user-3',
        ]);
        $this->assertAuthenticatedAs($user);
    }

    private function mockSocialiteUser(SocialUserContract $socialUser): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($socialUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }

    private function makeSocialUser(string $id, string $email, string $name): SocialUserContract
    {
        return new class($id, $email, $name) implements SocialUserContract {
            public string $token = 'social-token';
            public ?string $refreshToken = 'social-refresh-token';
            public ?int $expiresIn = 3600;

            public function __construct(
                private readonly string $id,
                private readonly string $email,
                private readonly string $name,
            ) {
            }

            public function getId()
            {
                return $this->id;
            }

            public function getNickname()
            {
                return null;
            }

            public function getName()
            {
                return $this->name;
            }

            public function getEmail()
            {
                return $this->email;
            }

            public function getAvatar()
            {
                return 'https://example.com/avatar.png';
            }
        };
    }
}