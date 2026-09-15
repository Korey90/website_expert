<?php

namespace Tests\Feature\Filament;

use App\Actions\Account\ChangePasswordAction;
use App\Actions\Account\UpdateAdminProfileAction;
use App\Models\User;
use Filament\Auth\Pages\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PragmaRX\Google2FAQRCode\Google2FA;
use Tests\TestCase;

class AccountProfileTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->artisan('db:seed', ['--class' => 'AdminSeeder']);
        Cache::flush();

        $this->user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('OldPassword1'),
            'phone' => '123456789',
            'locale' => 'en',
            'is_active' => true,
        ]);

        // Grant minimum permission to access the admin panel
        $this->user->givePermissionTo('access_admin_panel');
    }

    // ─── Page access ──────────────────────────────────────────────────

    public function test_admin_account_profile_page_is_accessible(): void
    {
        $response = $this->actingAs($this->user)->get('/admin/account-profile-page');

        $response->assertOk();
    }

    // ─── UpdateAdminProfileAction ─────────────────────────────────────

    public function test_update_admin_profile_updates_user_fields(): void
    {
        $action = app(UpdateAdminProfileAction::class);

        $action->execute($this->user, [
            'name' => 'New Name',
            'email' => 'new@example.com',
            'phone' => '987654321',
            'locale' => 'pl',
        ]);

        $this->user->refresh();

        $this->assertSame('New Name', $this->user->name);
        $this->assertSame('new@example.com', $this->user->email);
        $this->assertSame('987654321', $this->user->phone);
        $this->assertSame('pl', $this->user->locale);
        $this->assertNull($this->user->email_verified_at);
    }

    public function test_update_admin_profile_preserves_email_verified_when_email_unchanged(): void
    {
        $this->user->email_verified_at = now();
        $this->user->save();

        $action = app(UpdateAdminProfileAction::class);

        $action->execute($this->user, [
            'name' => 'Another Name',
            'email' => 'original@example.com',
            'phone' => null,
            'locale' => 'en',
        ]);

        $this->user->refresh();

        $this->assertNotNull($this->user->email_verified_at);
    }

    // ─── ChangePasswordAction ─────────────────────────────────────────

    public function test_change_password_succeeds_with_correct_current_password(): void
    {
        $action = app(ChangePasswordAction::class);

        $action->execute($this->user, [
            'current_password' => 'OldPassword1',
            'password' => 'NewPassword9',
        ]);

        $this->user->refresh();

        $this->assertTrue(Hash::check('NewPassword9', $this->user->password));
    }

    public function test_change_password_throws_when_current_password_wrong(): void
    {
        $this->expectException(ValidationException::class);

        $action = app(ChangePasswordAction::class);
        $action->execute($this->user, [
            'current_password' => 'WrongPassword',
            'password' => 'NewPassword9',
        ]);
    }

    // ─── App authentication (native Filament multi-factor) ────────────

    public function test_user_can_store_and_retrieve_app_authentication_secret(): void
    {
        $this->assertNull($this->user->getAppAuthenticationSecret());

        $this->user->saveAppAuthenticationSecret('SECRETKEY123456');
        $this->user->refresh();

        $this->assertSame('SECRETKEY123456', $this->user->getAppAuthenticationSecret());
    }

    public function test_login_without_two_factor_logs_in_directly(): void
    {
        Livewire::test(Login::class)
            ->set('data.email', $this->user->email)
            ->set('data.password', 'OldPassword1')
            ->call('authenticate');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_login_with_two_factor_enabled_requires_totp_challenge(): void
    {
        $google2fa = app(Google2FA::class);
        $this->user->saveAppAuthenticationSecret($google2fa->generateSecretKey());

        Livewire::test(Login::class)
            ->set('data.email', $this->user->email)
            ->set('data.password', 'OldPassword1')
            ->call('authenticate');

        // Credentials alone must NOT be enough to establish a session.
        $this->assertGuest();
    }

    public function test_login_with_two_factor_succeeds_with_valid_totp_code(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();
        $this->user->saveAppAuthenticationSecret($secret);

        $component = Livewire::test(Login::class)
            ->set('data.email', $this->user->email)
            ->set('data.password', 'OldPassword1')
            ->call('authenticate');

        $this->assertGuest();

        $component
            ->set('data.multiFactor.app.code', $google2fa->getCurrentOtp($secret))
            ->call('authenticate');

        $this->assertAuthenticatedAs($this->user);
    }

    public function test_login_with_two_factor_rejects_invalid_totp_code(): void
    {
        $google2fa = app(Google2FA::class);
        $this->user->saveAppAuthenticationSecret($google2fa->generateSecretKey());

        $component = Livewire::test(Login::class)
            ->set('data.email', $this->user->email)
            ->set('data.password', 'OldPassword1')
            ->call('authenticate');

        $component
            ->set('data.multiFactor.app.code', '000000')
            ->call('authenticate')
            ->assertHasErrors();

        $this->assertGuest();
    }
}
