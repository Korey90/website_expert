<?php

namespace Tests\Feature\Finance;

use App\Models\User;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use App\Services\Hmrc\HmrcFraudPreventionClient;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcFraudPreventionValidationSummary;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcFraudPreventionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-11-10 12:34:56 UTC');
        config()->set('hmrc.environment', 'sandbox');
        config()->set('hmrc.api_version', '1.0');
        config()->set('hmrc.timeout', 5);
        config()->set('hmrc.sandbox.base_url', 'https://test-api.service.hmrc.gov.uk');
        config()->set('hmrc.sandbox.token_url', 'https://test-api.service.hmrc.gov.uk/oauth/token');
        config()->set('hmrc.sandbox.client_id', 'sandbox-client-id');
        config()->set('hmrc.sandbox.client_secret', 'sandbox-client-secret');
        config()->set('hmrc.fraud_prevention.browser_context_cookie', 'hmrc_fph_context');
        config()->set('hmrc.fraud_prevention.browser_context_ttl', 1800);
        config()->set('hmrc.fraud_prevention.client_public_ip_header', null);
        config()->set('hmrc.fraud_prevention.client_public_port_header', null);
        config()->set('hmrc.fraud_prevention.product_name', 'Website Expert NI');
        config()->set('hmrc.fraud_prevention.product_id', 'website-expert-ni');
        config()->set('hmrc.fraud_prevention.product_version', '2026.11.10');
        config()->set('hmrc.fraud_prevention.vendor_public_ip', '1.1.1.1');
        config()->set('hmrc.fraud_prevention.vendor_forwarding_chain', '1.1.1.1');
        config()->set('hmrc.fraud_prevention.vendor_license_id', 'licence-123');
        config()->set('hmrc.fraud_prevention.allowed_missing_headers', []);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Request::setTrustedProxies([], 0);

        parent::tearDown();
    }

    public function test_builder_creates_complete_encoded_web_app_header_set(): void
    {
        $result = app(HmrcFraudPreventionHeaders::class)->build(
            $this->requestWithBrowserContext(),
            $this->user(),
            $this->multiFactor(),
        );

        $this->assertTrue($result->isComplete());
        $this->assertCount(16, $result->headers);
        $this->assertSame('WEB_APP_VIA_SERVER', $result->headers['Gov-Client-Connection-Method']);
        $this->assertSame('8.8.8.8', $result->headers['Gov-Client-Public-IP']);
        $this->assertSame('54321', $result->headers['Gov-Client-Public-Port']);
        $this->assertSame('UTC+01:00', $result->headers['Gov-Client-Timezone']);
        $this->assertSame('Website%20Expert%20NI', $result->headers['Gov-Vendor-Product-Name']);
        $this->assertSame('by=1.1.1.1&for=8.8.8.8', $result->headers['Gov-Vendor-Forwarded']);
        $this->assertStringContainsString('timestamp=2026-11-10T12%3A34%3A56Z', $result->headers['Gov-Client-Multi-Factor']);
        $this->assertStringContainsString('website-expert-ni-login=bookkeeper%40test.pl', $result->headers['Gov-Client-User-IDs']);
        $this->assertStringNotContainsString('licence-123', $result->headers['Gov-Vendor-License-IDs']);
        $this->assertSame(hash('sha256', 'licence-123'), explode('=', $result->headers['Gov-Vendor-License-IDs'], 2)[1]);

        app(HmrcFraudPreventionHeaders::class)->assertComplete($result->headers);
    }

    public function test_stale_browser_context_is_rejected_without_echoing_values(): void
    {
        $request = $this->requestWithBrowserContext([
            'collected_at' => '2026-11-10T10:00:00.000Z',
            'browser_js_user_agent' => 'secret-browser-value',
        ]);

        $result = app(HmrcFraudPreventionHeaders::class)->build($request, $this->user(), $this->multiFactor());

        $this->assertContains('Gov-Client-Browser-JS-User-Agent', $result->missingHeaders);
        $this->assertContains('Gov-Client-Device-ID', $result->missingHeaders);
        $this->assertNotContains('secret-browser-value', $result->headers);
    }

    public function test_builder_can_use_values_overwritten_by_a_trusted_edge(): void
    {
        config()->set('hmrc.fraud_prevention.client_public_ip_header', 'X-HMRC-Client-IP');
        config()->set('hmrc.fraud_prevention.client_public_port_header', 'X-HMRC-Client-Port');
        $request = $this->requestWithBrowserContext();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT);
        $request->headers->set('X-HMRC-Client-IP', '9.9.9.9');
        $request->headers->set('X-HMRC-Client-Port', '61234');

        $result = app(HmrcFraudPreventionHeaders::class)->build($request, $this->user(), $this->multiFactor());

        $this->assertTrue($result->isComplete());
        $this->assertSame('9.9.9.9', $result->headers['Gov-Client-Public-IP']);
        $this->assertSame('61234', $result->headers['Gov-Client-Public-Port']);
        $this->assertSame('by=1.1.1.1&for=9.9.9.9', $result->headers['Gov-Vendor-Forwarded']);
    }

    public function test_forwarded_for_uses_nearest_public_hop_and_does_not_reuse_the_proxy_port(): void
    {
        config()->set('hmrc.fraud_prevention.client_public_ip_header', 'X-Forwarded-For');
        config()->set('hmrc.fraud_prevention.client_public_port_header', null);
        $request = $this->requestWithBrowserContext();
        $request->server->set('REMOTE_ADDR', '127.0.0.1');
        $request->server->set('REMOTE_PORT', '58723');
        Request::setTrustedProxies(['127.0.0.1'], Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PORT);
        $request->headers->set('X-Forwarded-For', '8.8.8.8, 46.64.243.96, 10.32.1.250');
        $request->headers->set('X-Forwarded-Port', '443');

        $result = app(HmrcFraudPreventionHeaders::class)->build($request, $this->user(), $this->multiFactor());

        $this->assertSame('46.64.243.96', $result->headers['Gov-Client-Public-IP']);
        $this->assertArrayHasKey('Gov-Client-Public-IP-Timestamp', $result->headers);
        $this->assertArrayNotHasKey('Gov-Client-Public-Port', $result->headers);
        $this->assertContains('Gov-Client-Public-Port', $result->missingHeaders);
    }

    public function test_explicit_hmrc_exception_can_allow_a_genuinely_missing_header(): void
    {
        config()->set('hmrc.fraud_prevention.vendor_license_id', null);
        config()->set('hmrc.fraud_prevention.allowed_missing_headers', ['Gov-Vendor-License-IDs']);

        $result = app(HmrcFraudPreventionHeaders::class)->build(
            $this->requestWithBrowserContext(),
            $this->user(),
            $this->multiFactor(),
        );

        $this->assertTrue($result->isComplete());
        $this->assertArrayNotHasKey('Gov-Vendor-License-IDs', $result->headers);
        app(HmrcFraudPreventionHeaders::class)->assertComplete($result->headers);
    }

    public function test_validation_client_uses_client_credentials_and_sends_complete_headers(): void
    {
        Http::fake(function (HttpRequest $request) {
            if ($request->url() === 'https://test-api.service.hmrc.gov.uk/oauth/token') {
                return Http::response([
                    'access_token' => 'application-access-token',
                    'token_type' => 'bearer',
                    'expires_in' => 14400,
                ]);
            }

            return Http::response([
                'specVersion' => '3.3',
                'code' => 'VALID_HEADERS',
                'headers' => [],
            ], 200, ['X-CorrelationId' => 'fph-correlation']);
        });

        $headers = app(HmrcFraudPreventionHeaders::class)->build(
            $this->requestWithBrowserContext(),
            $this->user(),
            $this->multiFactor(),
        )->headers;

        $response = app(HmrcFraudPreventionClient::class)->validate($headers);

        $this->assertSame('VALID_HEADERS', $response->data['code']);
        $this->assertSame('fph-correlation', $response->correlationId);

        Http::assertSent(function (HttpRequest $request): bool {
            if ($request->url() !== 'https://test-api.service.hmrc.gov.uk/oauth/token') {
                return false;
            }

            return $request->isForm()
                && $request['grant_type'] === 'client_credentials'
                && $request['client_id'] === 'sandbox-client-id'
                && $request['client_secret'] === 'sandbox-client-secret'
                && ! str_contains($request->url(), 'sandbox-client-secret');
        });

        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/validate'
            && $request->hasHeader('Authorization', 'Bearer application-access-token')
            && $request->hasHeader('Accept', 'application/vnd.hmrc.1.0+json')
            && $request->hasHeader('Gov-Client-Connection-Method', 'WEB_APP_VIA_SERVER'));
    }

    public function test_feedback_uses_vat_mtd_and_connection_method_filter(): void
    {
        Http::fakeSequence()
            ->push(['access_token' => 'app-token', 'token_type' => 'bearer', 'expires_in' => 14400])
            ->push(['requests' => []], 200);

        app(HmrcFraudPreventionClient::class)->feedback();

        Http::assertSent(fn (HttpRequest $request): bool => str_starts_with(
            $request->url(),
            'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/vat-mtd/validation-feedback?',
        ) && str_contains($request->url(), 'connectionMethod=WEB_APP_VIA_SERVER'));
    }

    public function test_sandbox_validator_accepts_an_incomplete_set_for_diagnostics(): void
    {
        Http::fakeSequence()
            ->push(['access_token' => 'app-token', 'token_type' => 'bearer', 'expires_in' => 14400])
            ->push([
                'specVersion' => '3.3',
                'code' => 'INVALID_HEADERS',
                'headers' => [],
            ], 200);

        $response = app(HmrcFraudPreventionClient::class)->validate([
            'Gov-Client-Connection-Method' => 'WEB_APP_VIA_SERVER',
        ]);

        $this->assertSame('INVALID_HEADERS', $response->data['code']);
        Http::assertSent(fn (HttpRequest $request): bool => $request->url() === 'https://test-api.service.hmrc.gov.uk/test/fraud-prevention-headers/validate'
            && $request->hasHeader('Gov-Client-Connection-Method', 'WEB_APP_VIA_SERVER'));
    }

    public function test_validation_summary_reads_top_level_hmrc_errors_and_warnings(): void
    {
        $summary = app(HmrcFraudPreventionValidationSummary::class)->fromResponse(new HmrcApiResponse(
            200,
            [
                'specVersion' => '3.3',
                'code' => 'INVALID_HEADERS',
                'errors' => [[
                    'code' => 'MISSING_HEADER',
                    'message' => 'Header required',
                    'headers' => ['gov-client-public-port'],
                ]],
                'warnings' => [[
                    'code' => 'MISSING_HEADER',
                    'message' => 'Header required',
                    'headers' => ['gov-vendor-license-ids'],
                ]],
            ],
            'correlation-id',
        ));

        $this->assertFalse($summary['valid']);
        $this->assertSame(1, $summary['errors']);
        $this->assertSame(1, $summary['warnings']);
        $this->assertSame('gov-client-public-port', $summary['error_items'][0]['headers'][0]);
        $this->assertSame('MISSING_HEADER', $summary['warning_items'][0]['code']);
    }

    public function test_validation_is_blocked_outside_sandbox_before_http(): void
    {
        config()->set('hmrc.environment', 'production');
        Http::fake();

        $this->expectException(HmrcFraudPreventionException::class);
        $this->expectExceptionMessage('only in the sandbox');

        app(HmrcFraudPreventionClient::class)->validate($this->completeHeaders());
    }

    public function test_application_token_error_is_sanitised(): void
    {
        Http::fake([
            '*' => Http::response([
                'error' => 'invalid_client',
                'error_description' => 'secret sandbox-client-secret is invalid',
            ], 401),
        ]);

        try {
            app(HmrcFraudPreventionClient::class)->validate($this->completeHeaders());
            $this->fail('Invalid client credentials must fail.');
        } catch (HmrcFraudPreventionException $exception) {
            $this->assertStringContainsString('INVALID_CLIENT', $exception->getMessage());
            $this->assertStringNotContainsString('sandbox-client-secret', $exception->getMessage());
            $this->assertStringNotContainsString('error_description', $exception->getMessage());
        }
    }

    private function requestWithBrowserContext(array $overrides = []): Request
    {
        $context = array_replace_recursive([
            'browser_js_user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)',
            'device_id' => 'beec798b-b366-47fa-b1f8-92cede14a1ce',
            'screens' => [[
                'width' => 1920,
                'height' => 1080,
                'scaling_factor' => 1.25,
                'colour_depth' => 24,
            ]],
            'timezone' => 'UTC+01:00',
            'window_size' => ['width' => 1280, 'height' => 900],
            'collected_at' => '2026-11-10T12:34:30.000Z',
        ], $overrides);

        return Request::create(
            '/admin/hmrc-connection',
            'POST',
            [],
            ['hmrc_fph_context' => json_encode($context, JSON_THROW_ON_ERROR)],
            [],
            ['REMOTE_ADDR' => '8.8.8.8', 'REMOTE_PORT' => '54321'],
        );
    }

    private function user(): User
    {
        $user = new User;
        $user->forceFill(['id' => 42, 'email' => 'bookkeeper@test.pl']);

        return $user;
    }

    /** @return array{type: string, timestamp: string, unique_reference: string} */
    private function multiFactor(): array
    {
        return [
            'type' => 'TOTP',
            'timestamp' => '2026-11-10T12:34:56Z',
            'unique_reference' => str_repeat('a', 64),
        ];
    }

    /** @return array<string, string> */
    private function completeHeaders(): array
    {
        return array_combine(
            HmrcFraudPreventionHeaders::REQUIRED_HEADERS,
            array_fill(0, count(HmrcFraudPreventionHeaders::REQUIRED_HEADERS), 'test-value'),
        );
    }
}
