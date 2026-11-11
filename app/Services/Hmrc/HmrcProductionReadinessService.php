<?php

namespace App\Services\Hmrc;

final class HmrcProductionReadinessService
{
    public function __construct(
        private readonly HmrcEnvironmentGuard $guard,
    ) {}

    /** @return array{ready: bool, checked_at: string, checks: array<int, array{id: string, passed: bool, detail: string}>} */
    public function assess(): array
    {
        $production = (array) config('hmrc.production', []);
        $allowedMissing = (array) config('hmrc.fraud_prevention.allowed_missing_headers', []);
        $checks = [
            $this->check(
                'production_runtime',
                app()->environment('production') && config('app.debug') === false,
                'APP_ENV=production and APP_DEBUG=false',
            ),
            $this->check(
                'https_application_url',
                $this->isHttpsUrl(config('app.url')),
                'APP_URL uses HTTPS',
            ),
            $this->check(
                'secure_session',
                config('session.secure') === true
                    && config('session.http_only') === true
                    && config('session.encrypt') === true
                    && in_array(config('session.same_site'), ['lax', 'strict'], true),
                'secure, HTTP-only, encrypted SameSite session cookie',
            ),
            $this->check(
                'production_endpoints',
                $this->guard->productionConfigurationIsValid($production),
                'official HMRC hosts and HTTPS application callback',
            ),
            $this->check(
                'production_credentials',
                $this->filled($production['client_id'] ?? null) && $this->filled($production['client_secret'] ?? null),
                'production Client ID and secret are present',
            ),
            $this->check(
                'minimum_scopes',
                $this->hasMinimumScopes((array) config('hmrc.scopes', [])),
                'read:vat and write:vat scopes are configured',
            ),
            $this->check(
                'production_access_gate',
                ($production['access_enabled'] ?? false) === true
                    && $this->guard->validReference($production['approval_reference'] ?? null),
                'production access switch and HMRC approval reference',
            ),
            $this->check(
                'fph_validation_gate',
                $this->guard->validReference($production['fph_validation_reference'] ?? null),
                'successful production-shape FPH validation reference',
            ),
            $this->check(
                'security_review_gate',
                $this->guard->validReference($production['security_review_reference'] ?? null),
                'security and penetration-test sign-off reference',
            ),
            $this->check(
                'owner_approval_gate',
                $this->guard->validReference($production['owner_approval_reference'] ?? null),
                'explicit owner approval reference',
            ),
            $this->check(
                'production_submission_gate',
                ($production['submission_enabled'] ?? false) === true,
                'explicit production submission switch',
            ),
            $this->check(
                'fph_product_identity',
                $this->validProductId(config('hmrc.fraud_prevention.product_id'))
                    && $this->filled(config('hmrc.fraud_prevention.product_name'))
                    && $this->filled(config('hmrc.fraud_prevention.product_version'))
                    && $this->validPublicIp(config('hmrc.fraud_prevention.vendor_public_ip'))
                    && $this->validForwardingChain(config('hmrc.fraud_prevention.vendor_forwarding_chain')),
                'product version, vendor public IP and TLS forwarding chain are configured',
            ),
            $this->check(
                'fph_vendor_license',
                $this->filled(config('hmrc.fraud_prevention.vendor_license_id'))
                    || ($this->allowsMissing($allowedMissing, 'Gov-Vendor-License-IDs')
                        && $this->guard->validReference(config('hmrc.fraud_prevention.exception_reference'))),
                'vendor licence is configured or covered by an HMRC exception',
            ),
            $this->check(
                'fph_missing_header_exceptions',
                $this->validAllowedMissingHeaders($allowedMissing)
                    && ($allowedMissing === []
                        || $this->guard->validReference(config('hmrc.fraud_prevention.exception_reference'))),
                'every allowed missing FPH set has an HMRC exception reference',
            ),
            $this->check(
                'sandbox_scenarios_disabled',
                $this->sandboxScenariosAreEmpty(),
                'all Gov-Test-Scenario configuration is blank',
            ),
        ];

        return [
            'ready' => collect($checks)->every(fn (array $check): bool => $check['passed']),
            'checked_at' => now()->utc()->toIso8601String(),
            'checks' => $checks,
        ];
    }

    /** @return array{id: string, passed: bool, detail: string} */
    private function check(string $id, bool $passed, string $detail): array
    {
        return compact('id', 'passed', 'detail');
    }

    private function filled(mixed $value): bool
    {
        return is_string($value) && trim($value) !== '';
    }

    private function isHttpsUrl(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_URL) !== false
            && strtolower((string) parse_url($value, PHP_URL_SCHEME)) === 'https';
    }

    private function validProductId(mixed $value): bool
    {
        return is_string($value) && preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/', trim($value)) === 1;
    }

    private function validPublicIp(mixed $value): bool
    {
        return is_string($value)
            && filter_var(trim($value), FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function validForwardingChain(mixed $value): bool
    {
        if (! is_string($value) || trim($value) === '') {
            return false;
        }

        $addresses = array_map('trim', explode(',', $value));

        return $addresses !== []
            && collect($addresses)->every(fn (string $address): bool => $this->validPublicIp($address));
    }

    /** @param array<int, mixed> $allowedMissing */
    private function allowsMissing(array $allowedMissing, string $header): bool
    {
        return in_array(strtolower($header), array_map(
            fn (mixed $value): string => is_string($value) ? strtolower($value) : '',
            $allowedMissing,
        ), true);
    }

    /** @param array<int, mixed> $allowedMissing */
    private function validAllowedMissingHeaders(array $allowedMissing): bool
    {
        $required = array_map('strtolower', HmrcFraudPreventionHeaders::REQUIRED_HEADERS);

        return collect($allowedMissing)->every(fn (mixed $header): bool => is_string($header)
            && in_array(strtolower($header), $required, true));
    }

    /** @param array<int, mixed> $scopes */
    private function hasMinimumScopes(array $scopes): bool
    {
        return in_array('read:vat', $scopes, true) && in_array('write:vat', $scopes, true);
    }

    private function sandboxScenariosAreEmpty(): bool
    {
        foreach (['obligations', 'return', 'liabilities', 'payments', 'submission'] as $endpoint) {
            if ($this->filled(config("hmrc.sandbox.{$endpoint}_test_scenario"))) {
                return false;
            }
        }

        return true;
    }
}
