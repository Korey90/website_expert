<?php

namespace Tests\Feature\Finance;

use App\Models\AccountingProfile;
use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class HmrcOAuthFlowTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();

        config()->set([
            'hmrc.environment' => 'sandbox',
            'hmrc.oauth_state_ttl' => 600,
            'hmrc.scopes' => ['read:vat', 'write:vat'],
            'hmrc.sandbox.authorize_url' => 'https://test-www.tax.service.gov.uk/oauth/authorize',
            'hmrc.sandbox.token_url' => 'https://test-api.service.hmrc.gov.uk/oauth/token',
            'hmrc.sandbox.client_id' => 'sandbox-client-id',
            'hmrc.sandbox.client_secret' => 'sandbox-client-secret',
            'hmrc.sandbox.redirect_uri' => 'http://localhost/admin/hmrc/oauth/callback',
        ]);

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true, 'locale' => 'en']);
        Permission::findOrCreate('access_admin_panel', 'web');
        Permission::findOrCreate('view_finance', 'web');
        $this->owner->givePermissionTo(['access_admin_panel', 'view_finance']);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $this->owner->id,
            'role' => 'owner',
            'is_active' => true,
            'joined_at' => now(),
        ]);
        AccountingProfile::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'legal_name' => 'Website Expert NI',
            'vat_registered' => true,
            'vat_number' => 'GB123456789',
        ]);
    }

    public function test_connect_uses_state_and_pkce_without_exposing_the_client_secret(): void
    {
        $response = $this->actingAs($this->owner)->get(route('admin.hmrc.oauth.connect'));

        $response->assertRedirect();
        $location = (string) $response->headers->get('Location');
        $query = $this->queryFromUrl($location);

        $this->assertStringStartsWith('https://test-www.tax.service.gov.uk/oauth/authorize?', $location);
        $this->assertSame('code', $query['response_type']);
        $this->assertSame('sandbox-client-id', $query['client_id']);
        $this->assertSame('read:vat write:vat', $query['scope']);
        $this->assertSame('S256', $query['code_challenge_method']);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9_-]{43}$/', $query['code_challenge']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $query['state']);
        $this->assertStringNotContainsString('sandbox-client-secret', $location);

        $pending = session('hmrc.oauth.pending');
        $this->assertSame(hash('sha256', $query['state']), $pending['state_hash']);
        $this->assertNotSame($query['state'], $pending['state_hash']);
        $this->assertGreaterThanOrEqual(43, strlen(Crypt::decryptString($pending['code_verifier'])));
    }

    public function test_callback_exchanges_code_and_encrypts_tokens_at_rest_without_copying_the_real_vrn_to_sandbox(): void
    {
        Http::fake([
            'https://test-api.service.hmrc.gov.uk/oauth/token' => Http::response([
                'access_token' => 'plain-access-token',
                'refresh_token' => 'plain-refresh-token',
                'token_type' => 'bearer',
                'expires_in' => 14400,
                'scope' => 'read:vat write:vat',
            ]),
        ]);

        $state = $this->beginConnection();

        $this->get(route('admin.hmrc.oauth.callback', [
            'state' => $state,
            'code' => 'single-use-code',
        ]))
            ->assertRedirect(route('filament.admin.resources.vat-returns.index'))
            ->assertSessionHas('success');

        Http::assertSent(function (Request $request): bool {
            return $request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token'
                && $request->isForm()
                && $request['grant_type'] === 'authorization_code'
                && $request['client_id'] === 'sandbox-client-id'
                && $request['client_secret'] === 'sandbox-client-secret'
                && $request['redirect_uri'] === 'http://localhost/admin/hmrc/oauth/callback'
                && $request['code'] === 'single-use-code'
                && is_string($request['code_verifier'])
                && strlen($request['code_verifier']) >= 43;
        });

        $connection = HmrcConnection::withoutGlobalScopes()->sole();
        $raw = DB::table('hmrc_connections')->where('id', $connection->id)->first();

        $this->assertSame('plain-access-token', $connection->access_token);
        $this->assertSame('plain-refresh-token', $connection->refresh_token);
        $this->assertNull($connection->vrn);
        $this->assertSame(['read:vat', 'write:vat'], $connection->scopes);
        $this->assertSame(HmrcConnection::STATUS_CONNECTED, $connection->status);
        $this->assertNotSame('plain-access-token', $raw->access_token);
        $this->assertNotSame('plain-refresh-token', $raw->refresh_token);
        $this->assertNull($raw->vrn);
        $this->assertArrayNotHasKey('access_token', $connection->toArray());
        $this->assertArrayNotHasKey('refresh_token', $connection->toArray());
        $this->assertArrayNotHasKey('vrn', $connection->toArray());
        $this->assertNull(session('hmrc.oauth.pending'));

        $this->artisan('finance:audit-tenants')
            ->expectsOutputToContain('hmrc_connections')
            ->expectsOutputToContain('Finance tenant audit passed.')
            ->assertExitCode(0);
    }

    public function test_reauthorising_the_sandbox_preserves_the_explicit_test_vrn(): void
    {
        $connection = $this->connectedConnection('old-access-token', 'old-refresh-token');
        $connection->forceFill([
            'vrn' => '987654321',
            'vrn_updated_by' => $this->owner->id,
            'vrn_updated_at' => now()->subHour(),
        ])->save();

        Http::fake([
            'https://test-api.service.hmrc.gov.uk/oauth/token' => Http::response([
                'access_token' => 'new-access-token',
                'refresh_token' => 'new-refresh-token',
                'token_type' => 'bearer',
                'expires_in' => 14400,
                'scope' => 'read:vat write:vat',
            ]),
        ]);

        $state = $this->beginConnection();

        $this->get(route('admin.hmrc.oauth.callback', [
            'state' => $state,
            'code' => 'new-single-use-code',
        ]))->assertSessionHas('success');

        $connection->refresh();
        $this->assertSame('987654321', $connection->vrn);
        $this->assertSame($this->owner->id, $connection->vrn_updated_by);
        $this->assertSame('new-access-token', $connection->access_token);
    }

    public function test_callback_rejects_tampered_state_before_sending_tokens(): void
    {
        $this->beginConnection();

        $this->get(route('admin.hmrc.oauth.callback', [
            'state' => str_repeat('0', 64),
            'code' => 'intercepted-code',
        ]))
            ->assertRedirect(route('filament.admin.resources.vat-returns.index'))
            ->assertSessionHas('error', fn (string $message): bool => str_contains($message, 'invalid or has expired'));

        Http::assertNothingSent();
        $this->assertDatabaseCount('hmrc_connections', 0);
        $this->assertNull(session('hmrc.oauth.pending'));
    }

    public function test_denied_authorisation_is_sanitised_and_consumes_state(): void
    {
        $state = $this->beginConnection();

        $response = $this->get(route('admin.hmrc.oauth.callback', [
            'state' => $state,
            'error' => 'access_denied',
            'error_code' => '<script>sensitive</script>',
            'error_description' => 'do not expose this message',
        ]));

        $response
            ->assertRedirect(route('filament.admin.resources.vat-returns.index'))
            ->assertSessionHas('error', 'HMRC authorisation was not granted.');
        $this->assertStringNotContainsString('sensitive', (string) session('error'));
        $this->assertStringNotContainsString('do not expose', (string) session('error'));
        Http::assertNothingSent();
        $this->assertNull(session('hmrc.oauth.pending'));
    }

    public function test_oauth_failure_does_not_expose_code_secret_or_response_body(): void
    {
        Http::fake([
            'https://test-api.service.hmrc.gov.uk/oauth/token' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'sandbox-client-secret single-use-code raw-body-marker',
            ], 401),
        ]);
        $state = $this->beginConnection();

        $this->get(route('admin.hmrc.oauth.callback', [
            'state' => $state,
            'code' => 'single-use-code',
        ]))->assertSessionHas('error', function (string $message): bool {
            $this->assertStringContainsString('INVALID_CLIENT', $message);
            $this->assertStringNotContainsString('sandbox-client-secret', $message);
            $this->assertStringNotContainsString('single-use-code', $message);
            $this->assertStringNotContainsString('raw-body-marker', $message);

            return true;
        });

        $this->assertDatabaseCount('hmrc_connections', 0);
    }

    public function test_refresh_rotates_the_single_use_refresh_token_atomically(): void
    {
        $connection = $this->connectedConnection('old-access', 'old-refresh');
        Http::fake([
            'https://test-api.service.hmrc.gov.uk/oauth/token' => Http::response([
                'access_token' => 'new-access',
                'refresh_token' => 'new-refresh',
                'token_type' => 'bearer',
                'expires_in' => 14400,
                'scope' => 'read:vat write:vat',
            ]),
        ]);

        $this->actingAs($this->owner)
            ->post(route('admin.hmrc.oauth.refresh'))
            ->assertRedirect(route('filament.admin.resources.vat-returns.index'))
            ->assertSessionHas('success');

        Http::assertSent(fn (Request $request): bool => $request['grant_type'] === 'refresh_token'
            && $request['refresh_token'] === 'old-refresh'
            && $request['client_secret'] === 'sandbox-client-secret');

        $connection->refresh();
        $this->assertSame('new-access', $connection->access_token);
        $this->assertSame('new-refresh', $connection->refresh_token);
        $this->assertSame($this->owner->id, $connection->refreshed_by);
        $this->assertNotNull($connection->refreshed_at);
    }

    public function test_invalid_refresh_token_is_erased_and_requires_reconnect(): void
    {
        $connection = $this->connectedConnection('expired-access', 'invalid-refresh');
        Http::fake([
            'https://test-api.service.hmrc.gov.uk/oauth/token' => Http::response([
                'error' => 'invalid_grant',
                'error_description' => 'invalid-refresh must never be shown',
            ], 400),
        ]);

        $this->actingAs($this->owner)
            ->post(route('admin.hmrc.oauth.refresh'))
            ->assertSessionHas('error', function (string $message): bool {
                $this->assertStringNotContainsString('invalid-refresh', $message);

                return str_contains($message, 'INVALID_GRANT');
            });

        $connection->refresh();
        $this->assertSame(HmrcConnection::STATUS_RECONNECT_REQUIRED, $connection->status);
        $this->assertNull($connection->access_token);
        $this->assertNull($connection->refresh_token);
        $this->assertNull($connection->expires_at);
        $this->assertSame('INVALID_GRANT', $connection->reconnect_reason);
    }

    public function test_disconnect_only_clears_current_tenant_tokens(): void
    {
        $connection = $this->connectedConnection('current-access', 'current-refresh');
        [$otherBusiness, $otherConnection] = $this->otherTenantConnection();

        $this->actingAs($this->owner)
            ->delete(route('admin.hmrc.oauth.disconnect'))
            ->assertRedirect(route('filament.admin.resources.vat-returns.index'))
            ->assertSessionHas('success');

        $connection->refresh();
        $otherConnection->refresh();

        $this->assertSame(HmrcConnection::STATUS_DISCONNECTED, $connection->status);
        $this->assertNull($connection->access_token);
        $this->assertNull($connection->refresh_token);
        $this->assertSame($this->owner->id, $connection->disconnected_by);
        $this->assertSame('other-access', $otherConnection->access_token);
        $this->assertSame($otherBusiness->id, $otherConnection->business_id);
        Http::assertNothingSent();
    }

    public function test_regular_member_cannot_manage_hmrc_oauth(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        BusinessUser::create([
            'business_id' => $this->business->id,
            'user_id' => $member->id,
            'role' => 'member',
            'is_active' => true,
            'joined_at' => now(),
        ]);

        $this->actingAs($member)
            ->get(route('admin.hmrc.oauth.connect'))
            ->assertForbidden();

        Http::assertNothingSent();
    }

    public function test_connection_page_shows_only_masked_status_data(): void
    {
        $this->connectedConnection('page-access-secret', 'page-refresh-secret');
        HmrcVatObligation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'period_key' => '#001',
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'due_date' => '2026-11-07',
            'status' => HmrcVatObligation::STATUS_FULFILLED,
            'received_date' => '2026-11-01',
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);

        $this->actingAs($this->owner)
            ->get(route('filament.admin.pages.hmrc-connection'))
            ->assertOk()
            ->assertSee('HMRC Connection')
            ->assertSee('Validate fraud headers')
            ->assertSee('Set sandbox VRN')
            ->assertSee('Sync VAT obligations')
            ->assertSee('View VAT Return')
            ->assertSee('Retrieve liabilities and payments')
            ->assertSee('2026-07-01')
            ->assertSee('2026-11-07')
            ->assertSee('hmrc_fph_context', false)
            ->assertSee('•••••6789')
            ->assertDontSee('page-access-secret')
            ->assertDontSee('page-refresh-secret')
            ->assertDontSee('123456789')
            ->assertDontSee('#001');
    }

    private function beginConnection(): string
    {
        $response = $this->actingAs($this->owner)->get(route('admin.hmrc.oauth.connect'));
        $response->assertRedirect();

        return $this->queryFromUrl((string) $response->headers->get('Location'))['state'];
    }

    /** @return array<string, string> */
    private function queryFromUrl(string $url): array
    {
        parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

        return $query;
    }

    private function connectedConnection(string $accessToken, string $refreshToken): HmrcConnection
    {
        return HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat', 'write:vat'],
            'access_token' => $accessToken,
            'refresh_token' => $refreshToken,
            'vrn' => '123456789',
            'expires_at' => now()->subMinute(),
            'status' => HmrcConnection::STATUS_CONNECTED,
            'connected_at' => now()->subDay(),
            'connected_by' => $this->owner->id,
        ]);
    }

    /** @return array{Business, HmrcConnection} */
    private function otherTenantConnection(): array
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'slug' => 'other-business',
            'is_active' => true,
        ]);
        $otherConnection = HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $otherBusiness->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat'],
            'access_token' => 'other-access',
            'refresh_token' => 'other-refresh',
            'expires_at' => now()->addHours(4),
            'status' => HmrcConnection::STATUS_CONNECTED,
        ]);

        return [$otherBusiness, $otherConnection];
    }
}
