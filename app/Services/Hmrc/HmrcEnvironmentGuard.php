<?php

namespace App\Services\Hmrc;

use DomainException;

final class HmrcEnvironmentGuard
{
    private const ENDPOINTS = [
        'sandbox' => [
            'base_url' => 'https://test-api.service.hmrc.gov.uk',
            'authorize_url' => 'https://test-www.tax.service.gov.uk/oauth/authorize',
            'token_url' => 'https://test-api.service.hmrc.gov.uk/oauth/token',
        ],
        'production' => [
            'base_url' => 'https://api.service.hmrc.gov.uk',
            'authorize_url' => 'https://www.tax.service.gov.uk/oauth/authorize',
            'token_url' => 'https://api.service.hmrc.gov.uk/oauth/token',
        ],
    ];

    public function environment(): string
    {
        $environment = (string) config('hmrc.environment', 'sandbox');

        if (! array_key_exists($environment, self::ENDPOINTS)) {
            throw new DomainException(__('finance.hmrc_security.errors.configuration'));
        }

        return $environment;
    }

    public function assertVatRequestAllowed(bool $sandboxClient, string $baseUrl, bool $submission): void
    {
        $environment = $this->environment();

        if (($environment === 'sandbox') !== $sandboxClient) {
            throw new DomainException(__('finance.hmrc_security.errors.client_environment'));
        }

        $this->assertAccessAllowed();
        $this->assertExactUrl($baseUrl, self::ENDPOINTS[$environment]['base_url']);

        if ($submission) {
            $this->assertSubmissionAllowed();
        }
    }

    /** @param array<string, mixed> $configuration */
    public function assertOAuthConfiguration(string $environment, array $configuration): void
    {
        if ($environment !== $this->environment()) {
            throw new DomainException(__('finance.hmrc_security.errors.client_environment'));
        }

        $this->assertAccessAllowed();

        foreach (['base_url', 'authorize_url', 'token_url'] as $key) {
            $this->assertExactUrl($configuration[$key] ?? null, self::ENDPOINTS[$environment][$key]);
        }

        $this->assertRedirectUrl($configuration['redirect_uri'] ?? null, $environment);
    }

    public function assertApplicationTokenConfiguration(string $environment, mixed $tokenUrl): void
    {
        if ($environment !== $this->environment()) {
            throw new DomainException(__('finance.hmrc_security.errors.client_environment'));
        }

        $this->assertAccessAllowed();
        $this->assertExactUrl($tokenUrl, self::ENDPOINTS[$environment]['token_url']);
    }

    public function assertAccessAllowed(): void
    {
        if ($this->environment() !== 'production') {
            return;
        }

        if (config('hmrc.production.access_enabled') !== true) {
            throw new DomainException(__('finance.hmrc_security.errors.production_access_locked'));
        }

        if (! $this->validReference(config('hmrc.production.approval_reference'))) {
            throw new DomainException(__('finance.hmrc_security.errors.production_access_locked'));
        }

        $allowedMissing = (array) config('hmrc.fraud_prevention.allowed_missing_headers', []);

        if ($allowedMissing !== [] && ! $this->validReference(config('hmrc.fraud_prevention.exception_reference'))) {
            throw new DomainException(__('finance.hmrc_security.errors.fph_exception_reference'));
        }
    }

    public function assertSubmissionAllowed(): void
    {
        if ($this->environment() !== 'production') {
            return;
        }

        $this->assertAccessAllowed();

        if (config('hmrc.production.submission_enabled') !== true
            || ! $this->validReference(config('hmrc.production.fph_validation_reference'))
            || ! $this->validReference(config('hmrc.production.security_review_reference'))
            || ! $this->validReference(config('hmrc.production.owner_approval_reference'))) {
            throw new DomainException(__('finance.hmrc_security.errors.production_submission_locked'));
        }
    }

    /** @param array<string, mixed> $configuration */
    public function productionConfigurationIsValid(array $configuration): bool
    {
        try {
            foreach (['base_url', 'authorize_url', 'token_url'] as $key) {
                $this->assertExactUrl($configuration[$key] ?? null, self::ENDPOINTS['production'][$key]);
            }

            $this->assertRedirectUrl($configuration['redirect_uri'] ?? null, 'production');
        } catch (DomainException) {
            return false;
        }

        return true;
    }

    public function validReference(mixed $reference): bool
    {
        return is_string($reference)
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9._:\/-]{5,99}$/', $reference) === 1;
    }

    private function assertExactUrl(mixed $actual, string $expected): void
    {
        if (! is_string($actual) || rtrim($actual, '/') !== rtrim($expected, '/')) {
            throw new DomainException(__('finance.hmrc_security.errors.endpoint'));
        }
    }

    private function assertRedirectUrl(mixed $url, string $environment): void
    {
        if (! is_string($url) || filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new DomainException(__('finance.hmrc_security.errors.redirect_uri'));
        }

        $parts = parse_url($url);
        $appParts = parse_url((string) config('app.url'));
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = strtolower((string) ($parts['host'] ?? ''));
        $appHost = strtolower((string) ($appParts['host'] ?? ''));
        $path = $parts['path'] ?? '';
        $localSandbox = $environment === 'sandbox'
            && $scheme === 'http'
            && in_array($host, ['localhost', '127.0.0.1', '::1'], true);

        if (($scheme !== 'https' && ! $localSandbox)
            || $host === '' || $host !== $appHost
            || $path !== '/admin/hmrc/oauth/callback'
            || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || ($environment === 'production' && isset($parts['port']) && $parts['port'] !== 443)) {
            throw new DomainException(__('finance.hmrc_security.errors.redirect_uri'));
        }
    }
}
