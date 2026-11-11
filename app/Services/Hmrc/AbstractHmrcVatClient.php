<?php

namespace App\Services\Hmrc;

use App\Services\Hmrc\Contracts\HmrcClientInterface;
use App\Services\Hmrc\Data\HmrcApiResponse;
use App\Services\Hmrc\Exceptions\HmrcApiException;
use DateTimeImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

abstract class AbstractHmrcVatClient implements HmrcClientInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly bool $sandbox,
        private readonly string $apiVersion,
        private readonly int $timeout,
    ) {}

    public function retrieveVatObligations(
        string $accessToken,
        string $vrn,
        array $filters = [],
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse {
        $this->assertVrn($vrn);
        $this->assertDateRange($filters['from'] ?? null, $filters['to'] ?? null);

        if (isset($filters['status']) && ! in_array($filters['status'], ['O', 'F'], true)) {
            throw new InvalidArgumentException('HMRC obligation status must be O or F.');
        }

        return $this->send(
            'GET',
            "/organisations/vat/{$vrn}/obligations",
            $accessToken,
            array_filter([
                'from' => $filters['from'] ?? null,
                'to' => $filters['to'] ?? null,
                'status' => $filters['status'] ?? null,
            ], fn (mixed $value): bool => $value !== null && $value !== ''),
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    public function viewVatReturn(
        string $accessToken,
        string $vrn,
        string $periodKey,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse {
        $this->assertVrn($vrn);
        $this->assertPeriodKey($periodKey);

        return $this->send(
            'GET',
            "/organisations/vat/{$vrn}/returns/".rawurlencode($periodKey),
            $accessToken,
            [],
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    public function submitVatReturn(
        string $accessToken,
        string $vrn,
        array $payload,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse {
        $this->assertVrn($vrn);
        HmrcSandboxScenario::assertValidSubmissionScenario($testScenario);

        if ($payload === []) {
            throw new InvalidArgumentException('HMRC VAT return payload cannot be empty.');
        }

        return $this->send(
            'POST',
            "/organisations/vat/{$vrn}/returns",
            $accessToken,
            $payload,
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    public function retrieveVatLiabilities(
        string $accessToken,
        string $vrn,
        string $from,
        string $to,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse {
        return $this->retrieveForDateRange(
            'liabilities',
            $accessToken,
            $vrn,
            $from,
            $to,
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    public function retrieveVatPayments(
        string $accessToken,
        string $vrn,
        string $from,
        string $to,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse {
        return $this->retrieveForDateRange(
            'payments',
            $accessToken,
            $vrn,
            $from,
            $to,
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    /** @param array<string, string> $fraudPreventionHeaders */
    private function retrieveForDateRange(
        string $resource,
        string $accessToken,
        string $vrn,
        string $from,
        string $to,
        array $fraudPreventionHeaders,
        ?string $testScenario,
    ): HmrcApiResponse {
        $this->assertVrn($vrn);
        $this->assertDateRange($from, $to);

        return $this->send(
            'GET',
            "/organisations/vat/{$vrn}/{$resource}",
            $accessToken,
            ['from' => $from, 'to' => $to],
            $fraudPreventionHeaders,
            $testScenario,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, string>  $fraudPreventionHeaders
     */
    private function send(
        string $method,
        string $path,
        string $accessToken,
        array $data,
        array $fraudPreventionHeaders,
        ?string $testScenario,
    ): HmrcApiResponse {
        if ($testScenario !== null && ! $this->sandbox) {
            throw new InvalidArgumentException('Gov-Test-Scenario is only allowed in the HMRC sandbox.');
        }

        app(HmrcEnvironmentGuard::class)->assertVatRequestAllowed(
            $this->sandbox,
            $this->baseUrl,
            $method === 'POST',
        );

        if (trim($accessToken) === '') {
            throw new InvalidArgumentException('HMRC access token is required.');
        }

        try {
            $request = $this->request($accessToken, $fraudPreventionHeaders, $testScenario);
            $response = $method === 'POST'
                ? $request->post($path, $data)
                : $request->get($path, $data);
        } catch (ConnectionException $exception) {
            throw HmrcApiException::transport($exception);
        }

        return $this->toApiResponse($response);
    }

    /** @param array<string, string> $fraudPreventionHeaders */
    private function request(
        string $accessToken,
        array $fraudPreventionHeaders,
        ?string $testScenario,
    ): PendingRequest {
        app(HmrcFraudPreventionHeaders::class)->assertComplete($fraudPreventionHeaders);

        $headers = [];

        foreach ($fraudPreventionHeaders as $name => $value) {
            if (preg_match('/^Gov-/i', $name) === 1 && trim($value) !== '') {
                $headers[$name] = $value;
            }
        }

        if ($testScenario !== null && $testScenario !== '') {
            $headers['Gov-Test-Scenario'] = $testScenario;
        }

        return Http::baseUrl(rtrim($this->baseUrl, '/'))
            ->accept("application/vnd.hmrc.{$this->apiVersion}+json")
            ->withToken($accessToken)
            ->withHeaders($headers)
            ->timeout(max(1, $this->timeout));
    }

    private function toApiResponse(Response $response): HmrcApiResponse
    {
        if (! $response->successful()) {
            throw HmrcApiException::fromResponse($response);
        }

        $data = $response->json();
        $correlationId = $response->header('X-CorrelationId');

        return new HmrcApiResponse(
            $response->status(),
            is_array($data) ? $data : [],
            is_string($correlationId) && $correlationId !== '' ? $correlationId : null,
        );
    }

    private function assertVrn(string $vrn): void
    {
        if (preg_match('/^\d{9}$/', $vrn) !== 1) {
            throw new InvalidArgumentException('HMRC VRN must contain exactly 9 digits.');
        }
    }

    private function assertPeriodKey(string $periodKey): void
    {
        if (preg_match('/^[A-Za-z0-9#]{1,18}$/', $periodKey) !== 1) {
            throw new InvalidArgumentException('HMRC period key is invalid.');
        }
    }

    private function assertDateRange(?string $from, ?string $to): void
    {
        if ($from === null && $to === null) {
            return;
        }

        if ($from === null || $to === null || ! $this->isDate($from) || ! $this->isDate($to) || $from > $to) {
            throw new InvalidArgumentException('HMRC date range must contain valid YYYY-MM-DD dates with from not after to.');
        }

        $days = (new DateTimeImmutable($from))->diff(new DateTimeImmutable($to))->days;

        if ($days === false || $days > 366) {
            throw new InvalidArgumentException('HMRC date range cannot exceed 366 days.');
        }
    }

    private function isDate(string $value): bool
    {
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
