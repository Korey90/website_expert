<?php

namespace App\Services\Hmrc\Contracts;

use App\Services\Hmrc\Data\HmrcApiResponse;

interface HmrcClientInterface
{
    /**
     * @param  array{from?: string, to?: string, status?: 'O'|'F'}  $filters
     * @param  array<string, string>  $fraudPreventionHeaders
     */
    public function retrieveVatObligations(
        string $accessToken,
        string $vrn,
        array $filters = [],
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse;

    /** @param array<string, string> $fraudPreventionHeaders */
    public function viewVatReturn(
        string $accessToken,
        string $vrn,
        string $periodKey,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse;

    /**
     * @param  array<string, bool|int|float|string>  $payload
     * @param  array<string, string>  $fraudPreventionHeaders
     */
    public function submitVatReturn(
        string $accessToken,
        string $vrn,
        array $payload,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse;

    /** @param array<string, string> $fraudPreventionHeaders */
    public function retrieveVatLiabilities(
        string $accessToken,
        string $vrn,
        string $from,
        string $to,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse;

    /** @param array<string, string> $fraudPreventionHeaders */
    public function retrieveVatPayments(
        string $accessToken,
        string $vrn,
        string $from,
        string $to,
        array $fraudPreventionHeaders = [],
        ?string $testScenario = null,
    ): HmrcApiResponse;
}
