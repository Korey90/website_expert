<?php

namespace Tests\Feature\Finance;

use App\Services\Hmrc\HmrcEnvironmentGuard;
use App\Services\Hmrc\HmrcFraudPreventionHeaders;
use App\Services\Hmrc\HmrcProductionClient;
use App\Services\Hmrc\HmrcProductionReadinessService;
use DomainException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HmrcProductionSecurityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set([
            'app.url' => 'https://app.website-expert.test',
            'hmrc.environment' => 'production',
            'hmrc.api_version' => '1.0',
            'hmrc.timeout' => 5,
            'hmrc.scopes' => ['read:vat', 'write:vat'],
            'hmrc.fraud_prevention.allowed_missing_headers' => [],
            'hmrc.production' => [
                'access_enabled' => false,
                'submission_enabled' => false,
                'approval_reference' => null,
                'fph_validation_reference' => null,
                'security_review_reference' => null,
                'owner_approval_reference' => null,
                'base_url' => 'https://api.service.hmrc.gov.uk',
                'authorize_url' => 'https://www.tax.service.gov.uk/oauth/authorize',
                'token_url' => 'https://api.service.hmrc.gov.uk/oauth/token',
                'client_id' => 'production-client-id',
                'client_secret' => 'production-client-secret',
                'redirect_uri' => 'https://app.website-expert.test/admin/hmrc/oauth/callback',
            ],
        ]);

        Http::preventStrayRequests();
    }

    public function test_production_access_is_locked_before_any_http_request_by_default(): void
    {
        Http::fake();

        try {
            (new HmrcProductionClient)->retrieveVatObligations(
                'production-access-token',
                '123456789',
                [],
                $this->fraudHeaders(),
            );
            $this->fail('Production access must be explicitly unlocked.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_security.errors.production_access_locked'), $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_non_hmrc_production_endpoint_is_rejected_before_any_http_request(): void
    {
        $this->enableProductionReadAccess();
        config()->set('hmrc.production.base_url', 'https://attacker.example');
        Http::fake();

        try {
            (new HmrcProductionClient)->retrieveVatObligations(
                'production-access-token',
                '123456789',
                [],
                $this->fraudHeaders(),
            );
            $this->fail('A non-HMRC endpoint must be rejected.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_security.errors.endpoint'), $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_missing_fraud_header_allow_list_requires_an_hmrc_exception_reference(): void
    {
        $this->enableProductionReadAccess();
        config()->set('hmrc.fraud_prevention.allowed_missing_headers', ['Gov-Vendor-License-IDs']);
        Http::fake();

        try {
            (new HmrcProductionClient)->retrieveVatObligations(
                'production-access-token',
                '123456789',
                [],
                $this->fraudHeaders(),
            );
            $this->fail('An allowed missing header must have an HMRC exception reference.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_security.errors.fph_exception_reference'), $exception->getMessage());
        }

        Http::assertNothingSent();
    }

    public function test_read_access_can_be_enabled_without_unlocking_production_submissions(): void
    {
        $this->enableProductionReadAccess();
        Http::fake(fn (Request $request) => $request->method() === 'POST'
            ? Http::response(['processingDate' => '2026-11-11T12:00:00Z'], 201)
            : Http::response(['obligations' => []], 200));

        $client = new HmrcProductionClient;
        $response = $client->retrieveVatObligations(
            'production-access-token',
            '123456789',
            [],
            $this->fraudHeaders(),
        );

        $this->assertSame(200, $response->status);

        try {
            $client->submitVatReturn(
                'production-access-token',
                '123456789',
                ['periodKey' => '26A3', 'finalised' => true],
                $this->fraudHeaders(),
            );
            $this->fail('Production submission must remain separately locked.');
        } catch (DomainException $exception) {
            $this->assertSame(__('finance.hmrc_security.errors.production_submission_locked'), $exception->getMessage());
        }

        Http::assertSentCount(1);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'GET');

        config()->set([
            'hmrc.production.submission_enabled' => true,
            'hmrc.production.fph_validation_reference' => 'FPH-2026-001',
            'hmrc.production.security_review_reference' => 'SEC-2026-001',
            'hmrc.production.owner_approval_reference' => 'OWNER-2026-001',
        ]);

        $submitted = $client->submitVatReturn(
            'production-access-token',
            '123456789',
            ['periodKey' => '26A3', 'finalised' => true],
            $this->fraudHeaders(),
        );

        $this->assertSame(201, $submitted->status);
        Http::assertSentCount(2);
        Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
            && $request->url() === 'https://api.service.hmrc.gov.uk/organisations/vat/123456789/returns');
    }

    public function test_oauth_redirect_must_use_the_application_host_and_exact_callback_path(): void
    {
        $this->enableProductionReadAccess();
        $configuration = (array) config('hmrc.production');
        $configuration['redirect_uri'] = 'https://attacker.example/admin/hmrc/oauth/callback';

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage(__('finance.hmrc_security.errors.redirect_uri'));

        app(HmrcEnvironmentGuard::class)->assertOAuthConfiguration('production', $configuration);
    }

    public function test_readiness_report_is_fail_closed_and_never_contains_credentials(): void
    {
        config()->set([
            'hmrc.fraud_prevention.product_name' => 'Website Expert NI',
            'hmrc.fraud_prevention.product_id' => 'website-expert-ni',
            'hmrc.fraud_prevention.product_version' => '2026.11.11',
            'hmrc.fraud_prevention.vendor_public_ip' => '203.0.113.10',
            'hmrc.fraud_prevention.vendor_forwarding_chain' => '203.0.113.10',
        ]);

        $result = app(HmrcProductionReadinessService::class)->assess();
        $json = json_encode($result, JSON_THROW_ON_ERROR);

        $this->assertFalse($result['ready']);
        $this->assertFalse($this->checkPassed($result, 'production_runtime'));
        $this->assertFalse($this->checkPassed($result, 'production_access_gate'));
        $this->assertFalse($this->checkPassed($result, 'production_submission_gate'));
        $this->assertStringNotContainsString('production-client-id', $json);
        $this->assertStringNotContainsString('production-client-secret', $json);
    }

    public function test_readiness_report_passes_only_when_every_production_gate_is_satisfied(): void
    {
        $this->app['env'] = 'production';
        config()->set([
            'app.debug' => false,
            'session.secure' => true,
            'session.http_only' => true,
            'session.encrypt' => true,
            'session.same_site' => 'lax',
            'hmrc.production.access_enabled' => true,
            'hmrc.production.submission_enabled' => true,
            'hmrc.production.approval_reference' => 'HMRC-APPROVAL-2026-001',
            'hmrc.production.fph_validation_reference' => 'FPH-VALID-2026-001',
            'hmrc.production.security_review_reference' => 'PENTEST-2026-001',
            'hmrc.production.owner_approval_reference' => 'OWNER-2026-001',
            'hmrc.fraud_prevention.product_name' => 'Website Expert NI',
            'hmrc.fraud_prevention.product_id' => 'website-expert-ni',
            'hmrc.fraud_prevention.product_version' => '2026.11.11',
            'hmrc.fraud_prevention.vendor_public_ip' => '8.8.8.8',
            'hmrc.fraud_prevention.vendor_forwarding_chain' => '8.8.8.8,1.1.1.1',
            'hmrc.fraud_prevention.vendor_license_id' => 'production-licence-id',
            'hmrc.sandbox.obligations_test_scenario' => null,
            'hmrc.sandbox.return_test_scenario' => null,
            'hmrc.sandbox.liabilities_test_scenario' => null,
            'hmrc.sandbox.payments_test_scenario' => null,
            'hmrc.sandbox.submission_test_scenario' => null,
        ]);

        $result = app(HmrcProductionReadinessService::class)->assess();

        $this->assertTrue($result['ready']);
        $this->assertTrue(collect($result['checks'])->every(fn (array $check): bool => $check['passed']));
        $this->artisan('hmrc:production-readiness', ['--json' => true])
            ->assertExitCode(0);
    }

    public function test_readiness_command_returns_failure_while_any_gate_is_closed(): void
    {
        $this->artisan('hmrc:production-readiness', ['--json' => true])
            ->assertExitCode(1);
    }

    private function enableProductionReadAccess(): void
    {
        config()->set([
            'hmrc.production.access_enabled' => true,
            'hmrc.production.approval_reference' => 'HMRC-APPROVAL-2026-001',
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

    /** @param array{checks: array<int, array{id: string, passed: bool}>} $result */
    private function checkPassed(array $result, string $id): bool
    {
        foreach ($result['checks'] as $check) {
            if ($check['id'] === $id) {
                return $check['passed'];
            }
        }

        $this->fail("Readiness check [{$id}] was not found.");
    }
}
