<?php

namespace Tests\Feature\Filament;

use App\Actions\Account\ChangePasswordAction;
use App\Actions\Account\DisableTwoFactorAction;
use App\Actions\Account\EnableTwoFactorAction;
use App\Actions\Account\UpdateAdminProfileAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use PragmaRX\Google2FA\Google2FA;
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

    // ─── EnableTwoFactorAction ────────────────────────────────────────

    public function test_enable_two_factor_generates_secret_and_qr(): void
    {
        $action = app(EnableTwoFactorAction::class);

        $result = $action->generateSecret($this->user);

        $this->assertArrayHasKey('secret', $result);
        $this->assertArrayHasKey('qr_svg', $result);
        $this->assertNotEmpty($result['secret']);
        $this->assertNotEmpty($result['qr_svg']);

        $this->user->refresh();

        $this->assertNotNull($this->user->google_2fa_secret);
        $this->assertFalse($this->user->two_factor_enabled);
    }

    public function test_confirm_two_factor_enables_with_valid_code(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $this->user->google_2fa_secret = $secret;
        $this->user->two_factor_enabled = false;
        $this->user->save();

        $code = $google2fa->getCurrentOtp($secret);
        $action = app(EnableTwoFactorAction::class);
        $action->confirm($this->user, $code);

        $this->user->refresh();

        $this->assertTrue($this->user->two_factor_enabled);
    }

    public function test_confirm_two_factor_throws_on_invalid_code(): void
    {
        $this->expectException(ValidationException::class);

        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $this->user->google_2fa_secret = $secret;
        $this->user->save();

        $action = app(EnableTwoFactorAction::class);
        $action->confirm($this->user, '000000');
    }

    // ─── DisableTwoFactorAction ───────────────────────────────────────

    public function test_disable_two_factor_succeeds_with_valid_code(): void
    {
        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $this->user->google_2fa_secret = $secret;
        $this->user->two_factor_enabled = true;
        $this->user->save();

        $code = $google2fa->getCurrentOtp($secret);
        $action = app(DisableTwoFactorAction::class);
        $action->execute($this->user, $code);

        $this->user->refresh();

        $this->assertFalse($this->user->two_factor_enabled);
        $this->assertNull($this->user->google_2fa_secret);
    }

    public function test_disable_two_factor_throws_on_invalid_code(): void
    {
        $this->expectException(ValidationException::class);

        $google2fa = app(Google2FA::class);
        $secret = $google2fa->generateSecretKey();

        $this->user->google_2fa_secret = $secret;
        $this->user->two_factor_enabled = true;
        $this->user->save();

        $action = app(DisableTwoFactorAction::class);
        $action->execute($this->user, '000000');
    }
}
