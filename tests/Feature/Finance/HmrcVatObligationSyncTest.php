<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\User;
use App\Services\Hmrc\Exceptions\HmrcVatObligationSyncException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcVatObligationSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcVatObligationSyncTest extends TestCase
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
            'hmrc.sandbox.base_url' => 'https://test-api.service.hmrc.gov.uk',
            'hmrc.sandbox.token_url' => 'https://test-api.service.hmrc.gov.uk/oauth/token',
            'hmrc.sandbox.client_id' => 'sandbox-client-id',
            'hmrc.sandbox.client_secret' => 'sandbox-client-secret',
            'hmrc.sandbox.redirect_uri' => 'http://localhost/admin/hmrc/oauth/callback',
            'hmrc.api_version' => '1.0',
        ]);

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-obligations',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        $this->membership($this->business, $this->owner, 'owner');
        $this->connection($this->business, $this->owner, 'tenant-access-token');
    }

    public function test_sync_creates_tenant_obligations_without_exposing_period_keys(): void
    {
        Http::fake([
            'test-api.service.hmrc.gov.uk/*' => Http::response([
                'obligations' => [
                    [
                        'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                        'status' => 'O', 'periodKey' => '#001',
                    ],
                    [
                        'start' => '2026-04-01', 'end' => '2026-06-30', 'due' => '2026-08-07',
                        'status' => 'F', 'received' => '2026-08-01', 'periodKey' => '26A2',
                    ],
                ],
            ], 200, ['X-CorrelationId' => 'corr-sync-1']),
        ]);

        $result = $this->sync();

        $this->assertSame([
            'created' => 2,
            'updated' => 0,
            'unchanged' => 0,
            'total' => 2,
            'correlation_id' => 'corr-sync-1',
        ], $result);
        $this->assertDatabaseHas('hmrc_vat_obligations', [
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'period_key' => '#001',
            'status' => HmrcVatObligation::STATUS_OPEN,
            'received_date' => null,
        ]);
        $this->assertDatabaseHas('hmrc_vat_obligations', [
            'business_id' => $this->business->id,
            'period_key' => '26A2',
            'status' => HmrcVatObligation::STATUS_FULFILLED,
        ]);

        $obligation = HmrcVatObligation::withoutGlobalScopes()->where('period_key', '#001')->firstOrFail();
        $this->assertArrayNotHasKey('period_key', $obligation->toArray());
        $this->assertArrayNotHasKey('last_correlation_id', $obligation->toArray());
        $this->assertSame('corr-sync-1', $obligation->last_correlation_id);
        $this->assertNotNull($obligation->first_seen_at);
        $this->assertNotNull($obligation->status_changed_at);
        $this->assertSame(
            '2026-08-01',
            HmrcVatObligation::withoutGlobalScopes()->where('period_key', '26A2')->firstOrFail()->received_date->toDateString(),
        );

        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/organisations/vat/123456789/obligations?')
            && $request->hasHeader('Authorization', 'Bearer tenant-access-token'));
    }

    public function test_sync_is_idempotent_and_updates_open_obligation_when_hmrc_fulfils_it(): void
    {
        Http::fakeSequence()
            ->push(['obligations' => [[
                'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                'status' => 'O', 'periodKey' => '26A3',
            ]]], 200, ['X-CorrelationId' => 'corr-open'])
            ->push(['obligations' => [[
                'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                'status' => 'F', 'received' => '2026-11-05', 'periodKey' => '26A3',
            ]]], 200, ['X-CorrelationId' => 'corr-fulfilled'])
            ->push(['obligations' => [[
                'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                'status' => 'F', 'received' => '2026-11-05', 'periodKey' => '26A3',
            ]]], 200, ['X-CorrelationId' => 'corr-unchanged']);

        $this->assertSame(1, $this->sync()['created']);
        $firstSeen = HmrcVatObligation::withoutGlobalScopes()->sole()->first_seen_at;

        $this->travel(2)->minutes();
        $this->assertSame(1, $this->sync()['updated']);
        $fulfilled = HmrcVatObligation::withoutGlobalScopes()->sole();
        $this->assertSame(HmrcVatObligation::STATUS_FULFILLED, $fulfilled->status);
        $this->assertSame('2026-11-05', $fulfilled->received_date->toDateString());
        $this->assertTrue($fulfilled->first_seen_at->equalTo($firstSeen));
        $statusChangedAt = $fulfilled->status_changed_at;

        $this->travel(2)->minutes();
        $this->assertSame(1, $this->sync()['unchanged']);
        $unchanged = HmrcVatObligation::withoutGlobalScopes()->sole();
        $this->assertTrue($unchanged->status_changed_at->equalTo($statusChangedAt));
        $this->assertSame('corr-unchanged', $unchanged->last_correlation_id);
        $this->assertDatabaseCount('hmrc_vat_obligations', 1);
    }

    public function test_invalid_or_duplicate_hmrc_payload_is_rejected_without_partial_writes(): void
    {
        foreach ([
            ['unexpected' => []],
            ['obligations' => [
                [
                    'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                    'status' => 'O', 'periodKey' => '26A3',
                ],
                [
                    'start' => '2026-10-01', 'end' => '2026-12-31', 'due' => '2027-02-07',
                    'status' => 'O', 'periodKey' => '26A3',
                ],
            ]],
            ['obligations' => [[
                'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
                'status' => 'F', 'periodKey' => '26A3',
            ]]],
        ] as $payload) {
            Http::fake(['*' => Http::response($payload)]);

            try {
                $this->sync();
                $this->fail('An invalid HMRC response must be rejected.');
            } catch (HmrcVatObligationSyncException $exception) {
                $this->assertStringContainsString('invalid VAT obligations response', $exception->getMessage());
            }

            $this->assertDatabaseCount('hmrc_vat_obligations', 0);
        }
    }

    public function test_sync_enforces_active_owner_or_admin_membership_and_tenant_boundaries(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business', 'slug' => 'other-hmrc-obligations', 'is_active' => true,
        ]);
        $otherOwner = User::factory()->create(['is_active' => true]);
        $this->membership($otherBusiness, $otherOwner, 'owner');
        $this->connection($otherBusiness, $otherOwner, 'other-access-token');

        Http::fake(['*' => Http::response(['obligations' => [[
            'start' => '2026-07-01', 'end' => '2026-09-30', 'due' => '2026-11-07',
            'status' => 'O', 'periodKey' => '26A3',
        ]]])]);

        $this->expectException(HmrcVatObligationSyncException::class);
        $this->expectExceptionMessage('not allowed');

        app(HmrcVatObligationSyncService::class)->sync(
            $otherBusiness,
            $this->owner,
            ['from' => '2026-01-01', 'to' => '2026-12-31'],
            $this->fraudHeaders(),
        );
    }

    public function test_successful_empty_sync_keeps_history_and_marks_connection_call(): void
    {
        $existing = HmrcVatObligation::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'environment' => 'sandbox',
            'period_key' => '25A4',
            'start_date' => '2025-10-01',
            'end_date' => '2025-12-31',
            'due_date' => '2026-02-07',
            'status' => HmrcVatObligation::STATUS_FULFILLED,
            'received_date' => '2026-02-01',
            'first_seen_at' => now()->subYear(),
            'last_seen_at' => now()->subYear(),
        ]);
        Http::fake(['*' => Http::response(['obligations' => []])]);

        $result = $this->sync();

        $this->assertSame(0, $result['total']);
        $this->assertDatabaseHas('hmrc_vat_obligations', ['id' => $existing->id]);
        $this->assertNotNull(HmrcConnection::withoutGlobalScopes()->sole()->last_successful_call_at);
    }

    public function test_sync_refreshes_an_expiring_token_before_calling_obligations(): void
    {
        HmrcConnection::withoutGlobalScopes()->sole()->forceFill([
            'expires_at' => now()->subMinute(),
            'refresh_token' => 'single-use-refresh',
        ])->save();
        Http::fake(function (Request $request) {
            if ($request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token') {
                return Http::response([
                    'access_token' => 'rotated-access-token',
                    'refresh_token' => 'rotated-refresh-token',
                    'token_type' => 'bearer',
                    'expires_in' => 14400,
                    'scope' => 'read:vat write:vat',
                ]);
            }

            return Http::response(['obligations' => []], 200, ['X-CorrelationId' => 'corr-refreshed']);
        });

        $result = $this->sync();

        $this->assertSame('corr-refreshed', $result['correlation_id']);
        $connection = HmrcConnection::withoutGlobalScopes()->sole();
        $this->assertSame('rotated-access-token', $connection->access_token);
        $this->assertSame('rotated-refresh-token', $connection->refresh_token);
        $this->assertSame($this->owner->id, $connection->refreshed_by);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token'
            && $request['refresh_token'] === 'single-use-refresh');
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/obligations?')
            && $request->hasHeader('Authorization', 'Bearer rotated-access-token'));
    }

    /** @return array{created: int, updated: int, unchanged: int, total: int, correlation_id: ?string} */
    private function sync(): array
    {
        return app(HmrcVatObligationSyncService::class)->sync(
            $this->business,
            $this->owner,
            ['from' => '2026-01-01', 'to' => '2026-12-31'],
            $this->fraudHeaders(),
        );
    }

    private function connection(Business $business, User $actor, string $accessToken): HmrcConnection
    {
        return HmrcConnection::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'environment' => 'sandbox',
            'scopes' => ['read:vat', 'write:vat'],
            'access_token' => $accessToken,
            'refresh_token' => 'refresh-'.$accessToken,
            'vrn' => '123456789',
            'expires_at' => now()->addHours(3),
            'status' => HmrcConnection::STATUS_CONNECTED,
            'connected_at' => now(),
            'connected_by' => $actor->id,
        ]);
    }

    private function membership(Business $business, User $user, string $role): BusinessUser
    {
        return BusinessUser::create([
            'business_id' => $business->id,
            'user_id' => $user->id,
            'role' => $role,
            'is_active' => true,
            'joined_at' => now(),
        ]);
    }

    /** @return array<string, string> */
    private function fraudHeaders(): array
    {
        return array_combine(
            HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            array_map(
                fn (string $header): string => $header === 'Gov-Client-Connection-Method'
                    ? 'WEB_APP_VIA_SERVER'
                    : 'test-value',
                HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            ),
        );
    }
}
