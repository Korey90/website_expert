<?php

namespace Tests\Feature\Finance;

use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcProductionClient;
use App\Services\Hmrc\HmrcSandboxClient;
use App\Services\Hmrc\HmrcSandboxScenario;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

class HmrcVatClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('hmrc.api_version', '1.0');
        config()->set('hmrc.timeout', 5);
        config()->set('hmrc.sandbox.base_url', 'https://test-api.service.hmrc.gov.uk');
        config()->set('hmrc.production.base_url', 'https://api.service.hmrc.gov.uk');
    }

    public function test_sandbox_client_retrieves_obligations_with_version_auth_and_test_headers(): void
    {
        Http::fake([
            'test-api.service.hmrc.gov.uk/*' => Http::response([
                'obligations' => [[
                    'start' => '2026-07-01',
                    'end' => '2026-09-30',
                    'due' => '2026-11-07',
                    'status' => 'O',
                    'periodKey' => '26A3',
                ]],
            ], 200, ['X-CorrelationId' => 'corr-obligations']),
        ]);

        $response = (new HmrcSandboxClient)->retrieveVatObligations(
            'sandbox-access-token',
            '123456789',
            ['from' => '2026-07-01', 'to' => '2026-09-30', 'status' => 'O'],
            $this->fraudHeaders(),
            'QUARTERLY_NONE_MET',
        );

        $this->assertSame(200, $response->status);
        $this->assertSame('26A3', data_get($response->data, 'obligations.0.periodKey'));
        $this->assertSame('corr-obligations', $response->correlationId);

        Http::assertSent(function (Request $request): bool {
            parse_str((string) parse_url($request->url(), PHP_URL_QUERY), $query);

            return $request->method() === 'GET'
                && str_starts_with($request->url(), 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/obligations?')
                && $query === ['from' => '2026-07-01', 'to' => '2026-09-30', 'status' => 'O']
                && $request->hasHeader('Accept', 'application/vnd.hmrc.1.0+json')
                && $request->hasHeader('Authorization', 'Bearer sandbox-access-token')
                && $request->hasHeader('Gov-Client-Connection-Method', 'WEB_APP_VIA_SERVER')
                && $request->hasHeader('Gov-Test-Scenario', 'QUARTERLY_NONE_MET');
        });
    }

    public function test_client_submits_exact_vat_payload_without_automatic_retry(): void
    {
        Http::fakeSequence()
            ->push([
                'processingDate' => '2026-11-10T10:00:00Z',
                'formBundleNumber' => '123456789012',
                'paymentIndicator' => 'BANK',
                'chargeRefNumber' => 'A123456789012',
            ], 201, ['X-CorrelationId' => 'corr-submit'])
            ->push(['unexpected' => true], 200);

        $payload = [
            'periodKey' => '26A3',
            'vatDueSales' => 300.00,
            'vatDueAcquisitions' => 0.00,
            'totalVatDue' => 300.00,
            'vatReclaimedCurrPeriod' => 225.00,
            'netVatDue' => 75.00,
            'totalValueSalesExVAT' => 2100,
            'totalValuePurchasesExVAT' => 1450,
            'totalValueGoodsSuppliedExVAT' => 0,
            'totalAcquisitionsExVAT' => 0,
            'finalised' => true,
        ];

        $response = (new HmrcSandboxClient)->submitVatReturn(
            'sandbox-access-token',
            '123456789',
            $payload,
            $this->fraudHeaders(),
            null,
        );

        $this->assertSame(201, $response->status);
        $this->assertSame('123456789012', $response->data['formBundleNumber']);
        $this->assertSame('corr-submit', $response->correlationId);

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/returns'
            && ! $request->hasHeader('Gov-Test-Scenario')
            && $request->data() === $payload);
    }

    public function test_submission_scenarios_are_restricted_to_the_official_endpoint_catalogue(): void
    {
        Http::fake(['*' => Http::response([
            'code' => 'VRN_INVALID',
            'message' => 'Invalid VRN.',
        ], 400)]);
        $payload = ['periodKey' => '26A3', 'finalised' => true];

        try {
            (new HmrcSandboxClient)->submitVatReturn(
                'token',
                '123456789',
                $payload,
                $this->fraudHeaders(),
                HmrcSandboxScenario::SUBMISSION_INVALID_VRN,
            );
        } catch (HmrcApiException $exception) {
            $this->assertSame('VRN_INVALID', $exception->errorCode);
        }

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unsupported HMRC sandbox submission scenario.');

        (new HmrcSandboxClient)->submitVatReturn(
            'token',
            '123456789',
            $payload,
            $this->fraudHeaders(),
            'QUARTERLY_NONE_MET',
        );
    }

    public function test_client_supports_return_liability_and_payment_read_paths(): void
    {
        Http::fake([
            '*' => Http::response(['ok' => true], 200),
        ]);

        $client = new HmrcSandboxClient;
        $headers = $this->fraudHeaders();
        $client->viewVatReturn('token', '123456789', '26A3', $headers);
        $client->retrieveVatLiabilities('token', '123456789', '2026-01-01', '2026-12-31', $headers);
        $client->retrieveVatPayments('token', '123456789', '2026-01-01', '2026-12-31', $headers);

        Http::assertSentCount(3);
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/returns/26A3');
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/liabilities?'));
        Http::assertSent(fn (Request $request): bool => str_starts_with($request->url(), 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/payments?'));
    }

    public function test_client_encodes_special_period_keys_and_enforces_the_366_day_limit(): void
    {
        Http::fake(['*' => Http::response(['ok' => true])]);

        (new HmrcSandboxClient)->viewVatReturn('token', '123456789', '#001', $this->fraudHeaders());

        Http::assertSent(fn (Request $request): bool => $request->url()
            === 'https://test-api.service.hmrc.gov.uk/organisations/vat/123456789/returns/%23001');

        try {
            (new HmrcSandboxClient)->retrieveVatObligations(
                'token',
                '123456789',
                ['from' => '2025-01-01', 'to' => '2026-01-03'],
                $this->fraudHeaders(),
            );
            $this->fail('A date range greater than 366 days must be rejected.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('366 days', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_production_client_rejects_sandbox_scenario_header(): void
    {
        Http::fake();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Gov-Test-Scenario is only allowed in the HMRC sandbox.');

        (new HmrcProductionClient)->retrieveVatObligations(
            'production-access-token',
            '123456789',
            [],
            [],
            'DEFAULT',
        );
    }

    public function test_api_error_is_sanitised_and_does_not_retry_or_expose_sensitive_values(): void
    {
        Http::fakeSequence()
            ->push([
                'code' => 'VRN_INVALID',
                'message' => 'The supplied VAT registration number 123456789 is invalid for token secret-token.',
            ], 400, ['X-CorrelationId' => 'corr-error'])
            ->push(['unexpected' => true], 200);

        try {
            (new HmrcSandboxClient)->retrieveVatObligations('secret-token', '123456789', [], $this->fraudHeaders());
            $this->fail('An unsuccessful HMRC response must throw an exception.');
        } catch (HmrcApiException $exception) {
            $this->assertSame('VRN_INVALID', $exception->errorCode);
            $this->assertSame(400, $exception->status);
            $this->assertSame('corr-error', $exception->correlationId);
            $this->assertStringNotContainsString('123456789', $exception->getMessage());
            $this->assertStringNotContainsString('secret-token', $exception->getMessage());
            $this->assertStringNotContainsString('supplied VAT registration', $exception->getMessage());
        }

        Http::assertSentCount(1);
    }

    public function test_client_validates_identifiers_and_date_ranges_before_sending(): void
    {
        Http::fake();

        foreach ([
            fn () => (new HmrcSandboxClient)->retrieveVatObligations('token', '123'),
            fn () => (new HmrcSandboxClient)->viewVatReturn('token', '123456789', '../unsafe'),
            fn () => (new HmrcSandboxClient)->retrieveVatPayments('token', '123456789', '2026-12-31', '2026-01-01'),
        ] as $request) {
            try {
                $request();
                $this->fail('Invalid HMRC input must be rejected before sending.');
            } catch (InvalidArgumentException) {
                $this->addToAssertionCount(1);
            }
        }

        Http::assertNothingSent();
    }

    public function test_vat_request_fails_closed_when_fraud_headers_are_incomplete(): void
    {
        Http::fake();

        $this->expectException(HmrcFraudPreventionException::class);
        $this->expectExceptionMessage('Gov-Client-Device-ID');

        (new HmrcSandboxClient)->retrieveVatObligations(
            'token',
            '123456789',
            [],
            ['Gov-Client-Connection-Method' => 'WEB_APP_VIA_SERVER'],
        );
    }

    public function test_container_selects_client_from_explicit_environment(): void
    {
        config()->set('hmrc.environment', 'sandbox');
        $this->app->forgetInstance(HmrcClientInterface::class);
        $this->assertInstanceOf(HmrcSandboxClient::class, app(HmrcClientInterface::class));

        config()->set('hmrc.environment', 'production');
        $this->app->forgetInstance(HmrcClientInterface::class);
        $this->assertInstanceOf(HmrcProductionClient::class, app(HmrcClientInterface::class));
    }

    /** @return array<string, string> */
    private function fraudHeaders(): array
    {
        return array_combine(
            HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            array_map(fn (string $header): string => $header === 'Gov-Client-Connection-Method' ? 'WEB_APP_VIA_SERVER' : 'test-value', HmrcFraudPreventionHeaders::REQUIRED_HEADERS),
        );
    }
}
