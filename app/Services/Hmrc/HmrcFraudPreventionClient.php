<?php

namespace App\Services\Hmrc;

use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcFraudPreventionException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

class HmrcFraudPreventionClient
{
    public function __construct(
        private readonly HmrcApplicationTokenService $tokens,
    ) {}

    /** @param array<string, string> $headers */
    public function validate(array $headers): HmrcApiResponse
    {
        $this->assertSandbox();
        // The sandbox validator must also receive incomplete sets so it can
        // report the exact missing or invalid headers. Live VAT requests remain
        // fail-closed in AbstractHmrcVatClient.
        app(HmrcFraudPreventionHeaders::class)->assertSafe($headers);

        return $this->get('/test/fraud-prevention-headers/validate', $headers);
    }

    public function feedback(): HmrcApiResponse
    {
        $this->assertSandbox();

        return $this->get(
            '/test/fraud-prevention-headers/vat-mtd/validation-feedback',
            [],
            ['connectionMethod' => 'WEB_APP_VIA_SERVER'],
        );
    }

    /**
     * @param  array<string, string>  $headers
     * @param  array<string, string>  $query
     */
    private function get(string $path, array $headers, array $query = []): HmrcApiResponse
    {
        $baseUrl = rtrim((string) config('hmrc.sandbox.base_url'), '/');

        try {
            $response = Http::baseUrl($baseUrl)
                ->accept('application/vnd.hmrc.1.0+json')
                ->withToken($this->tokens->issue())
                ->withHeaders($headers)
                ->timeout(max(1, (int) config('hmrc.timeout', 20)))
                ->get($path, $query);
        } catch (ConnectionException) {
            throw HmrcFraudPreventionException::unreachable();
        }

        if (! $response->successful()) {
            throw HmrcFraudPreventionException::fromResponse($response);
        }

        return $this->response($response);
    }

    private function response(Response $response): HmrcApiResponse
    {
        $data = $response->json();
        $correlationId = $response->header('X-CorrelationId');

        return new HmrcApiResponse(
            $response->status(),
            is_array($data) ? $data : [],
            is_string($correlationId) && $correlationId !== '' ? $correlationId : null,
        );
    }

    private function assertSandbox(): void
    {
        if (config('hmrc.environment') !== 'sandbox') {
            throw HmrcFraudPreventionException::sandboxOnly();
        }
    }
}
