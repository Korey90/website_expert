<?php

namespace Tests\Feature\Finance;

use App\Models\Business;
use App\Models\BusinessUser;
use App\Models\HmrcConnection;
use App\Models\HmrcVatObligation;
use App\Models\HmrcVatSubmission;
use App\Models\User;
use App\Models\VatReturn;
use App\Services\Hmrc\Exceptions\HmrcVatReadException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcVatReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcVatReadTest extends TestCase
{
    use RefreshDatabase;

    private Business $business;

    private User $owner;

    private HmrcVatObligation $fulfilledObligation;

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
            'hmrc.api_version' => '1.0',
        ]);

        $this->business = Business::create([
            'name' => 'Website Expert NI',
            'slug' => 'website-expert-ni-vat-read',
            'is_active' => true,
        ]);
        $this->owner = User::factory()->create(['is_active' => true]);
        $this->membership($this->business, $this->owner, 'owner');
        $this->connection($this->business, $this->owner, 'tenant-access-token');
        $this->fulfilledObligation = $this->obligation($this->business, '26A3');
    }

    public function test_it_retrieves_and_normalizes_a_fulfilled_vat_return_without_exposing_the_period_key(): void
    {
        Http::fake(['*' => Http::response([
            'periodKey' => '26A3',
            'vatDueSales' => 105.25,
            'vatDueAcquisitions' => 10.50,
            'totalVatDue' => 115.75,
            'vatReclaimedCurrPeriod' => 40.25,
            'netVatDue' => 75.50,
            'totalValueSalesExVAT' => 1000,
            'totalValuePurchasesExVAT' => 400,
            'totalValueGoodsSuppliedExVAT' => 50,
            'totalAcquisitionsExVAT' => 25,
        ], 200, ['X-CorrelationId' => 'corr-return-1'])]);

        $result = app(HmrcVatReadService::class)->viewReturn(
            $this->business,
            $this->owner,
            $this->fulfilledObligation,
            $this->fraudHeaders(),
        );

        $this->assertSame(105.25, $result['boxes'][1]);
        $this->assertSame(25, $result['boxes'][9]);
        $this->assertSame('corr-return-1', $result['correlation_id']);
        $this->assertArrayNotHasKey('period_key', $result);
        $this->assertArrayNotHasKey('periodKey', $result);
        $this->assertNotNull(HmrcConnection::withoutGlobalScopes()->sole()->last_successful_call_at);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET'
            && str_contains($request->url(), '/organisations/vat/123456789/returns/26A3')
            && $request->hasHeader('Authorization', 'Bearer tenant-access-token'));
    }

    public function test_it_uses_the_canonical_stored_period_key_instead_of_a_mutated_model_value(): void
    {
        $this->fulfilledObligation->period_key = 'FORGED';
        Http::fake(['*' => Http::response([
            'periodKey' => '26A3',
            'vatDueSales' => 1.00,
            'vatDueAcquisitions' => 2.00,
            'totalVatDue' => 3.00,
            'vatReclaimedCurrPeriod' => 1.00,
            'netVatDue' => 2.00,
            'totalValueSalesExVAT' => 10,
            'totalValuePurchasesExVAT' => 5,
            'totalValueGoodsSuppliedExVAT' => 0,
            'totalAcquisitionsExVAT' => 0,
        ])]);

        app(HmrcVatReadService::class)->viewReturn(
            $this->business,
            $this->owner,
            $this->fulfilledObligation,
            $this->fraudHeaders(),
        );

        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/returns/26A3')
            && ! str_contains($request->url(), 'FORGED'));
    }

    public function test_missing_sandbox_return_is_explained_without_exposing_the_period_key(): void
    {
        Http::fake(['*' => Http::response('', 404)]);

        try {
            app(HmrcVatReadService::class)->viewReturn(
                $this->business,
                $this->owner,
                $this->fulfilledObligation,
                $this->fraudHeaders(),
            );
            $this->fail('A missing submitted return must be explained.');
        } catch (HmrcVatReadException $exception) {
            $this->assertSame(__('finance.hmrc_connection.errors.return_not_found'), $exception->getMessage());
            $this->assertStringNotContainsString('26A3', $exception->getMessage());
        }

        $this->assertNull(HmrcConnection::withoutGlobalScopes()->sole()->last_successful_call_at);
    }

    public function test_it_retrieves_and_normalizes_liabilities_and_payments(): void
    {
        Http::fake(function (Request $request) {
            if (str_contains($request->url(), '/liabilities?')) {
                return Http::response(['liabilities' => [[
                    'taxPeriod' => ['from' => '2026-07-01', 'to' => '2026-09-30'],
                    'type' => 'VAT Return Debit Charge',
                    'originalAmount' => 125.50,
                    'outstandingAmount' => 25.50,
                    'due' => '2026-11-07',
                ]]], 200, ['X-CorrelationId' => 'corr-liability']);
            }

            return Http::response(['payments' => [[
                'amount' => 100.00,
                'received' => '2026-11-01',
            ]]], 200, ['X-CorrelationId' => 'corr-payment']);
        });

        $result = app(HmrcVatReadService::class)->retrieveAccount(
            $this->business,
            $this->owner,
            '2026-01-01',
            '2026-11-11',
            $this->fraudHeaders(),
        );

        $this->assertSame('VAT Return Debit Charge', $result['liabilities'][0]['type']);
        $this->assertSame(25.50, $result['liabilities'][0]['outstanding_amount']);
        $this->assertSame('2026-11-01', $result['payments'][0]['received']);
        $this->assertSame('corr-liability', $result['liabilities_correlation_id']);
        $this->assertSame('corr-payment', $result['payments_correlation_id']);
        Http::assertSentCount(2);
    }

    public function test_optional_outstanding_due_and_received_fields_may_be_absent(): void
    {
        Http::fakeSequence()
            ->push(['liabilities' => [[
                'taxPeriod' => ['from' => '2026-07-01', 'to' => '2026-09-30'],
                'type' => 'VAT Return Credit Charge',
                'originalAmount' => -50.00,
            ]]], 200)
            ->push(['payments' => [['amount' => 50.00]]], 200);

        $result = app(HmrcVatReadService::class)->retrieveAccount(
            $this->business,
            $this->owner,
            '2026-01-01',
            '2026-11-11',
            $this->fraudHeaders(),
        );

        $this->assertNull($result['liabilities'][0]['outstanding_amount']);
        $this->assertNull($result['liabilities'][0]['due']);
        $this->assertNull($result['payments'][0]['received']);
    }

    public function test_account_read_rejects_an_unsupported_date_range_before_http(): void
    {
        Http::fake();

        $this->expectException(HmrcVatReadException::class);
        $this->expectExceptionMessage(__('finance.hmrc_connection.errors.account_date_range'));

        try {
            app(HmrcVatReadService::class)->retrieveAccount(
                $this->business,
                $this->owner,
                '2017-01-01',
                '2017-12-31',
                $this->fraudHeaders(),
            );
        } finally {
            Http::assertNothingSent();
        }
    }

    public function test_invalid_hmrc_payloads_are_rejected_and_not_presented(): void
    {
        Http::fake(['*' => Http::response([
            'periodKey' => 'different-period',
            'vatDueSales' => 1,
        ])]);

        try {
            app(HmrcVatReadService::class)->viewReturn(
                $this->business,
                $this->owner,
                $this->fulfilledObligation,
                $this->fraudHeaders(),
            );
            $this->fail('A mismatched VAT Return must be rejected.');
        } catch (HmrcVatReadException $exception) {
            $this->assertStringContainsString('invalid VAT Return response', $exception->getMessage());
        }

        $this->assertNull(HmrcConnection::withoutGlobalScopes()->sole()->last_successful_call_at);

        Http::fakeSequence()
            ->push(['liabilities' => [['taxPeriod' => ['from' => 'not-a-date']]]])
            ->push(['payments' => []]);

        $this->expectException(HmrcVatReadException::class);
        $this->expectExceptionMessage('invalid VAT liabilities response');

        app(HmrcVatReadService::class)->retrieveAccount(
            $this->business,
            $this->owner,
            '2026-01-01',
            '2026-11-11',
            $this->fraudHeaders(),
        );
    }

    public function test_it_rejects_an_open_or_cross_tenant_obligation(): void
    {
        $open = $this->obligation($this->business, '26A4', HmrcVatObligation::STATUS_OPEN);

        try {
            app(HmrcVatReadService::class)->viewReturn(
                $this->business,
                $this->owner,
                $open,
                $this->fraudHeaders(),
            );
            $this->fail('An open obligation must not be used to view a VAT Return.');
        } catch (HmrcVatReadException $exception) {
            $this->assertStringContainsString('fulfilled VAT obligation', $exception->getMessage());
        }

        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'slug' => 'other-vat-read',
            'is_active' => true,
        ]);
        $otherOwner = User::factory()->create(['is_active' => true]);
        $this->membership($otherBusiness, $otherOwner, 'owner');
        $this->connection($otherBusiness, $otherOwner, 'other-access-token');
        $otherObligation = $this->obligation($otherBusiness, '26B1');

        $this->expectException(HmrcVatReadException::class);
        $this->expectExceptionMessage('fulfilled VAT obligation');

        app(HmrcVatReadService::class)->viewReturn(
            $this->business,
            $this->owner,
            $otherObligation,
            $this->fraudHeaders(),
        );
    }

    public function test_it_can_view_an_open_sandbox_obligation_after_a_verified_local_submission(): void
    {
        $open = $this->obligation($this->business, '26A4', HmrcVatObligation::STATUS_OPEN);
        $this->submittedReturn($open);
        Http::fake(['*' => Http::response([
            'periodKey' => '26A4',
            'vatDueSales' => 0,
            'vatDueAcquisitions' => 0,
            'totalVatDue' => 0,
            'vatReclaimedCurrPeriod' => 0,
            'netVatDue' => 0,
            'totalValueSalesExVAT' => 0,
            'totalValuePurchasesExVAT' => 0,
            'totalValueGoodsSuppliedExVAT' => 0,
            'totalAcquisitionsExVAT' => 0,
        ], 200, ['X-CorrelationId' => 'corr-submitted-return'])]);

        $result = app(HmrcVatReadService::class)->viewReturn(
            $this->business,
            $this->owner,
            $open,
            $this->fraudHeaders(),
        );

        $this->assertSame(array_fill(1, 9, 0), $result['boxes']);
        $this->assertSame('corr-submitted-return', $result['correlation_id']);
        $this->assertTrue(HmrcVatObligation::withoutGlobalScopes()->whereKey($open->id)->returnViewable()->exists());
        Http::assertSent(fn (Request $request): bool => str_contains($request->url(), '/returns/26A4'));
    }

    public function test_it_enforces_active_owner_or_admin_membership(): void
    {
        $member = User::factory()->create(['is_active' => true]);
        $this->membership($this->business, $member, 'member');

        $this->expectException(HmrcVatReadException::class);
        $this->expectExceptionMessage('not allowed');

        app(HmrcVatReadService::class)->retrieveAccount(
            $this->business,
            $member,
            '2026-01-01',
            '2026-11-11',
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

    private function obligation(
        Business $business,
        string $periodKey,
        string $status = HmrcVatObligation::STATUS_FULFILLED,
    ): HmrcVatObligation {
        return HmrcVatObligation::withoutGlobalScopes()->create([
            'business_id' => $business->id,
            'environment' => 'sandbox',
            'period_key' => $periodKey,
            'start_date' => '2026-07-01',
            'end_date' => '2026-09-30',
            'due_date' => '2026-11-07',
            'status' => $status,
            'received_date' => $status === HmrcVatObligation::STATUS_FULFILLED ? '2026-11-01' : null,
            'first_seen_at' => now(),
            'last_seen_at' => now(),
        ]);
    }

    private function submittedReturn(HmrcVatObligation $obligation): HmrcVatSubmission
    {
        $return = VatReturn::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'number' => 'VATR-SANDBOX-001',
            'status' => VatReturn::STATUS_DRAFT,
            'period_from' => $obligation->start_date,
            'period_to' => $obligation->end_date,
            'snapshot_version' => 1,
            'snapshot' => [],
            'snapshot_hash' => str_repeat('a', 64),
            'source_journal_count' => 0,
            'source_line_count' => 0,
            'warning_count' => 0,
            'created_by' => $this->owner->id,
        ]);
        $return->update(['status' => VatReturn::STATUS_REVIEWED]);
        $return->update(['status' => VatReturn::STATUS_LOCKED]);

        return HmrcVatSubmission::withoutGlobalScopes()->create([
            'business_id' => $this->business->id,
            'vat_return_id' => $return->id,
            'hmrc_vat_obligation_id' => $obligation->id,
            'environment' => 'sandbox',
            'status' => HmrcVatSubmission::STATUS_SUBMITTED,
            'snapshot_hash' => $return->snapshot_hash,
            'payload_hash' => str_repeat('b', 64),
            'declaration_version' => HmrcVatSubmission::DECLARATION_VERSION,
            'declared_at' => now(),
            'mfa_type' => 'TOTP',
            'mfa_verified_at' => now(),
            'mfa_reference_hash' => str_repeat('c', 64),
            'submitted_by' => $this->owner->id,
            'submitted_at' => now(),
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
