<?php

namespace App\Services\Hmrc;

use App\Models\User;
use App\Services\Hmrc\Data\HmrcFraudPreventionResult;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Throwable;

class HmrcFraudPreventionHeaders
{
    /** @var array<int, string> */
    public const REQUIRED_HEADERS = [
        'Gov-Client-Connection-Method',
        'Gov-Client-Browser-JS-User-Agent',
        'Gov-Client-Device-ID',
        'Gov-Client-Multi-Factor',
        'Gov-Client-Public-IP',
        'Gov-Client-Public-IP-Timestamp',
        'Gov-Client-Public-Port',
        'Gov-Client-Screens',
        'Gov-Client-Timezone',
        'Gov-Client-User-IDs',
        'Gov-Client-Window-Size',
        'Gov-Vendor-Forwarded',
        'Gov-Vendor-License-IDs',
        'Gov-Vendor-Product-Name',
        'Gov-Vendor-Public-IP',
        'Gov-Vendor-Version',
    ];

    /**
     * @param  array{type?: string, timestamp?: string, unique_reference?: string}|null  $multiFactor
     */
    public function build(Request $request, User $user, ?array $multiFactor = null): HmrcFraudPreventionResult
    {
        $headers = ['Gov-Client-Connection-Method' => 'WEB_APP_VIA_SERVER'];
        $context = $this->browserContext($request);

        if ($context !== null) {
            $this->addBrowserHeaders($headers, $context);
        }

        $this->addMultiFactorHeader($headers, $multiFactor);
        $this->addRequestHeaders($headers, $request);
        $this->addUserHeader($headers, $user);
        $this->addVendorHeaders($headers);

        return new HmrcFraudPreventionResult($headers, $this->missingHeaders($headers));
    }

    /** @param array<string, string> $headers */
    public function assertComplete(array $headers): void
    {
        $this->assertSafe($headers);

        $missing = $this->missingHeaders($headers);

        if ($missing !== []) {
            throw HmrcFraudPreventionException::missing($missing);
        }
    }

    /** @param array<string, string> $headers */
    public function assertSafe(array $headers): void
    {
        foreach ($headers as $name => $value) {
            if (! is_string($name) || ! is_string($value) || str_contains($name.$value, "\r") || str_contains($name.$value, "\n")) {
                throw HmrcFraudPreventionException::invalidHeader();
            }
        }
    }

    /** @param array<string, mixed> $context */
    private function addBrowserHeaders(array &$headers, array $context): void
    {
        $userAgent = $this->singleLine($context['browser_js_user_agent'] ?? null, 1024);
        if ($userAgent !== null) {
            $headers['Gov-Client-Browser-JS-User-Agent'] = $this->encodeNonAscii($userAgent);
        }

        $deviceId = strtolower((string) ($context['device_id'] ?? ''));
        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $deviceId) === 1) {
            $headers['Gov-Client-Device-ID'] = $deviceId;
        }

        $screens = $this->screens($context['screens'] ?? null);
        if ($screens !== null) {
            $headers['Gov-Client-Screens'] = $screens;
        }

        $timezone = (string) ($context['timezone'] ?? '');
        if (preg_match('/^UTC[+-](?:0\d|1\d|2[0-3]):[0-5]\d$/', $timezone) === 1) {
            $headers['Gov-Client-Timezone'] = $timezone;
        }

        $window = $this->dimensions($context['window_size'] ?? null);
        if ($window !== null) {
            $headers['Gov-Client-Window-Size'] = "width={$window['width']}&height={$window['height']}";
        }
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array{type?: string, timestamp?: string, unique_reference?: string}|null  $multiFactor
     */
    private function addMultiFactorHeader(array &$headers, ?array $multiFactor): void
    {
        if ($multiFactor === null) {
            return;
        }

        $type = strtoupper((string) ($multiFactor['type'] ?? ''));
        $timestamp = (string) ($multiFactor['timestamp'] ?? '');
        $reference = strtolower((string) ($multiFactor['unique_reference'] ?? ''));

        if (! in_array($type, ['TOTP', 'AUTH_CODE', 'OTHER'], true)
            || ! $this->validUtcTimestamp($timestamp)
            || preg_match('/^[a-f0-9]{64}$/', $reference) !== 1) {
            return;
        }

        $headers['Gov-Client-Multi-Factor'] = $this->keyValue([
            'type' => $type,
            'timestamp' => $timestamp,
            'unique-reference' => $reference,
        ]);
    }

    /** @param array<string, string> $headers */
    private function addRequestHeaders(array &$headers, Request $request): void
    {
        $clientIp = $this->clientPublicIp($request);
        if ($clientIp !== null) {
            $headers['Gov-Client-Public-IP'] = $clientIp;
            $headers['Gov-Client-Public-IP-Timestamp'] = now()->utc()->format('Y-m-d\TH:i:s.v\Z');
        }

        $publicPort = $this->clientPublicPort($request);
        $port = filter_var($publicPort, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 65535],
        ]);
        if ($port !== false) {
            $headers['Gov-Client-Public-Port'] = (string) $port;
        }
    }

    /** @param array<string, string> $headers */
    private function addUserHeader(array &$headers, User $user): void
    {
        $headers['Gov-Client-User-IDs'] = $this->keyValue([
            'website-expert-ni-user-id' => (string) $user->getAuthIdentifier(),
            'website-expert-ni-login' => (string) $user->email,
        ]);
    }

    /** @param array<string, string> $headers */
    private function addVendorHeaders(array &$headers): void
    {
        $config = (array) config('hmrc.fraud_prevention', []);
        $productName = $this->singleLine($config['product_name'] ?? null, 255);
        $productId = $this->productId($config['product_id'] ?? null);
        $version = $this->singleLine($config['product_version'] ?? null, 100);
        $vendorIp = is_string($config['vendor_public_ip'] ?? null) ? trim($config['vendor_public_ip']) : '';

        if ($productName !== null) {
            $headers['Gov-Vendor-Product-Name'] = rawurlencode($productName);
        }

        if ($productId !== null && $version !== null) {
            $headers['Gov-Vendor-Version'] = $this->keyValue([$productId => $version]);
        }

        if ($this->isPublicIp($vendorIp)) {
            $headers['Gov-Vendor-Public-IP'] = $vendorIp;
        }

        $clientIp = $headers['Gov-Client-Public-IP'] ?? null;
        $chain = $this->forwardingChain($config['vendor_forwarding_chain'] ?? null, $vendorIp);
        if ($clientIp !== null && $chain !== []) {
            $forwarded = [];
            $sender = $clientIp;
            foreach ($chain as $receiver) {
                $forwarded[] = $this->keyValue(['by' => $receiver, 'for' => $sender]);
                $sender = $receiver;
            }
            $headers['Gov-Vendor-Forwarded'] = implode(',', $forwarded);
        }

        $license = $this->singleLine($config['vendor_license_id'] ?? null, 512);
        if ($productId !== null && $license !== null) {
            $headers['Gov-Vendor-License-IDs'] = $this->keyValue([
                $productId => hash('sha256', $license),
            ]);
        }
    }

    /** @return array<string, mixed>|null */
    private function browserContext(Request $request): ?array
    {
        $cookieName = (string) config('hmrc.fraud_prevention.browser_context_cookie', 'hmrc_fph_context');
        $raw = $request->cookie($cookieName);

        if (! is_string($raw) || $raw === '' || strlen($raw) > 4096) {
            return null;
        }

        $context = json_decode($raw, true);
        if (! is_array($context)) {
            return null;
        }

        try {
            $collectedAt = CarbonImmutable::parse((string) ($context['collected_at'] ?? ''));
        } catch (Throwable) {
            return null;
        }

        $ttl = max(60, (int) config('hmrc.fraud_prevention.browser_context_ttl', 1800));
        $now = CarbonImmutable::now('UTC');

        if ($collectedAt->isBefore($now->subSeconds($ttl)) || $collectedAt->isAfter($now->addMinute())) {
            return null;
        }

        return $context;
    }

    private function screens(mixed $value): ?string
    {
        if (! is_array($value) || $value === [] || count($value) > 10) {
            return null;
        }

        $screens = [];
        foreach ($value as $screen) {
            $dimensions = $this->dimensions($screen);
            $scaling = filter_var(is_array($screen) ? ($screen['scaling_factor'] ?? null) : null, FILTER_VALIDATE_FLOAT);
            $colourDepth = filter_var(is_array($screen) ? ($screen['colour_depth'] ?? null) : null, FILTER_VALIDATE_INT);

            if ($dimensions === null || $scaling === false || $scaling <= 0 || $scaling > 10
                || $colourDepth === false || $colourDepth < 1 || $colourDepth > 128) {
                return null;
            }

            $screens[] = "width={$dimensions['width']}&height={$dimensions['height']}&scaling-factor={$scaling}&colour-depth={$colourDepth}";
        }

        return implode(',', $screens);
    }

    /** @return array{width: int, height: int}|null */
    private function dimensions(mixed $value): ?array
    {
        if (! is_array($value)) {
            return null;
        }

        $width = filter_var($value['width'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);
        $height = filter_var($value['height'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 100000]]);

        return $width === false || $height === false ? null : ['width' => $width, 'height' => $height];
    }

    /** @param array<string, scalar> $values */
    private function keyValue(array $values): string
    {
        $parts = [];
        foreach ($values as $key => $value) {
            $parts[] = rawurlencode($key).'='.rawurlencode((string) $value);
        }

        return implode('&', $parts);
    }

    /** @return array<int, string> */
    private function forwardingChain(mixed $configured, string $vendorIp): array
    {
        $chain = is_string($configured) && trim($configured) !== ''
            ? array_map('trim', explode(',', $configured))
            : [$vendorIp];

        if ($chain === [] || array_filter($chain, fn (string $ip): bool => ! $this->isPublicIp($ip)) !== []) {
            return [];
        }

        return array_values($chain);
    }

    /** @param array<string, string> $headers */
    private function missingHeaders(array $headers): array
    {
        $present = [];
        foreach ($headers as $name => $value) {
            if (is_string($value) && trim($value) !== '') {
                $present[strtolower($name)] = true;
            }
        }

        $allowed = array_map(
            'strtolower',
            array_filter((array) config('hmrc.fraud_prevention.allowed_missing_headers', []), 'is_string'),
        );

        // The Test API reports a warning (not an error) when a browser-based
        // SaaS product has no genuine licence installed on the originating
        // device. Permit only that observed sandbox condition so end-to-end
        // test journeys can run without fabricating a licence. Production
        // continues to require a real value or a referenced HMRC exception.
        if (config('hmrc.environment') === 'sandbox'
            && config('hmrc.fraud_prevention.sandbox_allow_missing_vendor_license') === true) {
            $allowed[] = strtolower('Gov-Vendor-License-IDs');
        }

        return array_values(array_filter(
            self::REQUIRED_HEADERS,
            fn (string $header): bool => ! isset($present[strtolower($header)]) && ! in_array(strtolower($header), $allowed, true),
        ));
    }

    private function productId(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : '';

        return preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/', $value) === 1 ? $value : null;
    }

    private function singleLine(mixed $value, int $maxLength): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value !== '' && strlen($value) <= $maxLength && ! preg_match('/[\x00-\x1F\x7F]/', $value)
            ? $value
            : null;
    }

    private function encodeNonAscii(string $value): string
    {
        return preg_replace_callback('/[^\x20-\x7E]/', fn (array $match): string => rawurlencode($match[0]), $value) ?? '';
    }

    private function isPublicIp(string $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function validUtcTimestamp(string $value): bool
    {
        try {
            $timestamp = CarbonImmutable::createFromFormat('!Y-m-d\TH:i:s\Z', $value, 'UTC');
        } catch (Throwable) {
            return false;
        }

        return $timestamp !== false
            && $timestamp->format('Y-m-d\TH:i:s\Z') === $value
            && ! $timestamp->isAfter(CarbonImmutable::now('UTC')->addMinute());
    }

    private function trustedRequestValue(Request $request, mixed $headerName, mixed $fallback): mixed
    {
        if (! is_string($headerName) || trim($headerName) === '') {
            return $fallback;
        }

        if (! $request->isFromTrustedProxy()) {
            return null;
        }

        $headerName = trim($headerName);
        if (preg_match('/^[A-Za-z0-9-]{1,100}$/', $headerName) !== 1) {
            return null;
        }

        return $request->header($headerName);
    }

    private function clientPublicIp(Request $request): ?string
    {
        $configuredHeader = config('hmrc.fraud_prevention.client_public_ip_header');
        $value = $this->trustedRequestValue($request, $configuredHeader, $request->ip());

        if (! is_string($value)) {
            return null;
        }

        // A trusted edge may append its own hops to an existing X-Forwarded-For
        // value. Walk from the receiving side and select the nearest public hop,
        // so client-supplied values prepended on the left cannot win.
        $candidates = array_reverse(array_map('trim', explode(',', $value)));
        foreach ($candidates as $candidate) {
            if ($this->isPublicIp($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    private function clientPublicPort(Request $request): mixed
    {
        $configuredHeader = config('hmrc.fraud_prevention.client_public_port_header');

        if (is_string($configuredHeader) && trim($configuredHeader) !== '') {
            return $this->trustedRequestValue($request, $configuredHeader, null);
        }

        // REMOTE_PORT belongs to the final proxy hop when a reverse proxy is in
        // use. Reporting it as the originating device's public port is invalid.
        return $request->isFromTrustedProxy() ? null : $request->server('REMOTE_PORT');
    }
}
